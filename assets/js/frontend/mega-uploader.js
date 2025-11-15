/**
 * Mega uploader helper built around megajs browser bundle.
 * Exposes window.PunaTikTokMegaUploader for use inside main.js.
 */
(function (window) {
    if (typeof window === 'undefined') {
        return;
    }

    function PunaTikTokMegaUploader(config) {
        this.email = config?.email || '';
        this.password = config?.password || '';
        this.uploadFolder = config?.folder || '/tiktok-video';
        this.storage = null;
        this.loginPromise = null;
    }

    PunaTikTokMegaUploader.prototype.login = function () {
        if (typeof mega === 'undefined' || typeof mega.Storage !== 'function') {
            return Promise.reject(new Error('Mega SDK not loaded.'));
        }
        if (!this.email || !this.password) {
            return Promise.reject(new Error('Missing Mega.nz login credentials.'));
        }
        if (this.storage && this.storage.status === 'ready') {
            return Promise.resolve(this.storage);
        }
        if (this.loginPromise) {
            return this.loginPromise;
        }

        console.log('[MegaUploader] Logging in to Mega.nz…');
        this.loginPromise = new Promise((resolve, reject) => {
            const fail = (err) => {
                this.loginPromise = null;
                reject(err instanceof Error ? err : new Error(String(err)));
            };

            try {
                const storage = new mega.Storage({
                    email: this.email,
                    password: this.password,
                    keepalive: false,
                    autoload: true
                });

                const onReady = () => {
                    console.log('[MegaUploader] Login successful');
                    storage.removeListener?.('error', onError);
                    this.storage = storage;
                    resolve(storage);
                };

                const onError = (err) => {
                    console.error('[MegaUploader] Login error', err);
                    storage.removeListener?.('ready', onReady);
                    fail(err);
                };

                if (typeof storage.once === 'function') {
                    storage.once('ready', onReady);
                    storage.once('error', onError);
                } else if (storage.ready && typeof storage.ready.then === 'function') {
                    storage.ready.then(onReady).catch(onError);
                } else {
                    onReady();
                }
            } catch (error) {
                console.error('[MegaUploader] Login exception', error);
                fail(error);
            }
        });

        return this.loginPromise;
    };

    PunaTikTokMegaUploader.prototype.findFolder = function (parent, name) {
        if (!parent || !parent.children) {
            return null;
        }
        const children = Array.isArray(parent.children)
            ? parent.children
            : Object.values(parent.children);
        return children.find((node) => node && node.directory && node.name === name);
    };

    PunaTikTokMegaUploader.prototype.ensureFolder = async function () {
        const storage = await this.login();
        const segments = (this.uploadFolder || '').split('/').filter(Boolean);
        if (!segments.length) {
            return storage.root;
        }

        let current = storage.root;
        for (const segment of segments) {
            console.log('[MegaUploader] Checking folder', segment);
            let child = this.findFolder(current, segment);
            if (!child && typeof current.mkdir === 'function') {
                child = await current.mkdir(segment);
                console.log('[MegaUploader] Created folder', segment);
            }
            current = child || current;
        }

        return current || storage.root;
    };

    PunaTikTokMegaUploader.prototype.uploadFile = async function (file, onProgress) {
        if (!file) {
            throw new Error('No video file provided.');
        }

        const targetFolder = await this.ensureFolder();
        if (!targetFolder || typeof targetFolder.upload !== 'function') {
            throw new Error('Cannot access Mega folder.');
        }

        const arrayBuffer = await file.arrayBuffer();
        const buffer = new Uint8Array(arrayBuffer);
        const totalBytes = buffer.length || file.size || 0;

        console.log('[MegaUploader] Starting upload', file.name, 'size:', totalBytes);
        if (typeof onProgress === 'function') {
            onProgress(0, totalBytes);
        }

        return new Promise((resolve, reject) => {
            try {
                const stream = targetFolder.upload(
                    {
                        name: file.name,
                        size: buffer.length,
                        allowUploadBuffering: true
                    },
                    buffer
                );

                if (!stream || typeof stream.on !== 'function') {
                    finish(new Error('Mega SDK did not return a valid stream.'));
                    return;
                }

                let settled = false;
                const finish = (err, uploadedFile) => {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    clearTimeout(timeoutId);

                    if (err) {
                        reject(err instanceof Error ? err : new Error(String(err)));
                        return;
                    }

                    resolve(uploadedFile);
                };

                const timeoutId = setTimeout(() => {
                    console.warn('[MegaUploader] Upload timeout after 120s');
                    finish(new Error('Upload to Mega.nz took too long. Please try again.'));
                }, 120000);

                if (stream.stream && typeof stream.stream.once === 'function') {
                    stream.stream.once('finish', () => {
                        if (typeof onProgress === 'function') {
                            onProgress(totalBytes, totalBytes);
                        }
                    });
                }

                stream.on('progress', (evt) => {
                    if (typeof onProgress === 'function') {
                        const uploaded = evt?.bytesUploaded ?? evt?.bytesLoaded ?? 0;
                        const total = evt?.bytesTotal ?? totalBytes;
                        onProgress(uploaded, total);
                    }
                    if (evt?.bytesUploaded != null && evt?.bytesTotal) {
                        console.log('[MegaUploader] progress', Math.round((evt.bytesUploaded / evt.bytesTotal) * 100), '%');
                    }
                });

                stream.once('error', (err) => {
                    console.error('[MegaUploader] error', err);
                    finish(err);
                });

                const handleSuccess = async (uploadedFile) => {
                    try {
                        const link = typeof uploadedFile.link === 'function'
                            ? await uploadedFile.link()
                            : '';

                        finish(null, {
                            link,
                            nodeId: uploadedFile?.nodeId || uploadedFile?.handle || '',
                            name: uploadedFile?.name || file.name,
                            size: file.size || buffer.length || 0
                        });
                        console.log('[MegaUploader] Upload successful', file.name);
                    } catch (error) {
                        finish(error);
                    }
                };

                if (typeof stream.once === 'function') {
                    stream.once('complete', handleSuccess);
                }

                if (stream.complete && typeof stream.complete.then === 'function') {
                    stream.complete.then(handleSuccess).catch((err) => {
                        console.error('[MegaUploader] Promise error', err);
                        finish(err);
                    });
                }
            } catch (error) {
                reject(error);
            }
        });
    };

    window.PunaTikTokMegaUploader = PunaTikTokMegaUploader;
})(window);
