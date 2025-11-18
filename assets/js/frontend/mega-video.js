/**
 * Mega.nz video handling
 */

const megaVideoCache = new Map();

async function ensureMegaVideoSource(video) {
    if (!video) return;
    const megaLink = video.dataset.megaLink;
    if (!megaLink || video.dataset.megaLoaded === '1') {
        return;
    }
    if (video.dataset.megaLoading === '1') {
        return;
    }
    if (typeof mega === 'undefined' || typeof mega.File !== 'function') {
        return;
    }

    video.dataset.megaLoading = '1';

    try {
        if (megaVideoCache.has(megaLink)) {
            video.src = megaVideoCache.get(megaLink);
            video.dataset.megaLoaded = '1';
            video.dataset.megaLoading = '0';
            return;
        }

        const megaFile = await Promise.resolve(mega.File.fromURL(megaLink));
        const buffer = await megaFile.downloadBuffer();
        const mime = video.dataset.megaMime || 'video/mp4';
        const objectUrl = URL.createObjectURL(new Blob([buffer], { type: mime }));
        megaVideoCache.set(megaLink, objectUrl);
        video.src = objectUrl;
        video.dataset.megaLoaded = '1';
        
        video.addEventListener('loadedmetadata', () => {
            video.classList.add('loaded');
        });
    } catch (error) {
        if (typeof showToast !== 'undefined') {
            showToast('Cannot load video from Mega.nz', 'error');
        }
    } finally {
        video.dataset.megaLoading = '0';
    }
}

