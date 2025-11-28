/**
 * Search panel functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const searchTrigger = document.getElementById('search-trigger');
    const closeSearchBtn = document.getElementById('close-search');
    const searchPanel = document.getElementById('search-panel');
    const realSearchInput = document.getElementById('real-search-input');
    const searchSuggestionsList = document.getElementById('search-suggestions-list');
    const searchHistorySection = document.getElementById('search-history-section');
    const searchHistoryList = document.getElementById('search-history-list');
    const searchPopularSection = document.getElementById('search-popular-section');
    const clearHistoryBtn = document.getElementById('clear-history-btn');
    
    let searchDebounceTimer = null;
    let currentSearchQuery = '';
    
    function isMobile() {
        return window.innerWidth < 1024;
    }
    
    function openSearchPanel() {
        document.body.classList.add('search-panel-active');
        document.body.classList.remove('search-panel-hover'); // Remove hover class on click
        setTimeout(() => {
            if (realSearchInput) {
                realSearchInput.focus();
                loadSearchHistory();
                loadPopularSearches();
            }
        }, 300);
    }

    function closeSearchPanel() {
        document.body.classList.remove('search-panel-active');
        document.body.classList.remove('search-panel-hover');
    }

    if (searchTrigger) {
        // Click only works on mobile (< 1024px)
        // PC only has hover

        let clickHandler = null;

        function setupClickHandler() {
            // Remove old handler if exists
            if (clickHandler) {
                searchTrigger.removeEventListener('click', clickHandler);
                clickHandler = null;
            }

            // Only add click handler on mobile
            if (isMobile()) {
                clickHandler = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (document.body.classList.contains('search-panel-active')) {
                        closeSearchPanel();
                    } else {
                        openSearchPanel();
                    }
                };
                searchTrigger.addEventListener('click', clickHandler);
            }
        }

        // Initialize click handler
        setupClickHandler();

        // Hover to show search panel (PC only >= 1024px)
        let hoverTimeout = null;
        
        function handleSearchHover() {
            // Only add hover event if screen >= 1024px
            if (!isMobile()) {
                searchTrigger.addEventListener('mouseenter', function() {
                    if (hoverTimeout) {
                        clearTimeout(hoverTimeout);
                        hoverTimeout = null;
                    }
                    document.body.classList.add('search-panel-hover');
                    // Load data immediately on hover
                    loadSearchHistory();
                    loadPopularSearches();
                });

                searchTrigger.addEventListener('mouseleave', function() {
                    // Delay to allow mouse movement to search panel
                    hoverTimeout = setTimeout(function() {
                        if (searchPanel && !searchPanel.matches(':hover')) {
                            document.body.classList.remove('search-panel-hover');
                        }
                    }, 150);
                });
            }

            // Keep search panel visible when hovering over it (PC only)
            if (searchPanel && !isMobile()) {
                searchPanel.addEventListener('mouseenter', function() {
                    if (hoverTimeout) {
                        clearTimeout(hoverTimeout);
                        hoverTimeout = null;
                    }
                    document.body.classList.add('search-panel-hover');
                    // Load data if not loaded yet (when hovering directly into panel)
                    if (!searchHistoryList || searchHistoryList.children.length === 0) {
                        loadSearchHistory();
                    }
                    if (!document.getElementById('search-popular-list') || document.getElementById('search-popular-list').children.length === 0) {
                        loadPopularSearches();
                    }
                });

                searchPanel.addEventListener('mouseleave', function() {
                    document.body.classList.remove('search-panel-hover');
                });
            }
        }

        handleSearchHover();
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (isMobile()) {
                // On mobile: remove hover class, setup click handler
                document.body.classList.remove('search-panel-hover');
                setupClickHandler();
            } else {
                // On PC: remove active class if exists (only uses hover)
                if (document.body.classList.contains('search-panel-active')) {
                    closeSearchPanel();
                }
                setupClickHandler(); // Remove click handler on PC
            }
        });
    }

    if (closeSearchBtn) {
        closeSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSearchPanel();
        });
    }

    // Bottom nav search trigger (mobile only)
    const bottomSearchTrigger = document.getElementById('bottom-search-trigger');
    if (bottomSearchTrigger) {
        bottomSearchTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (document.body.classList.contains('search-panel-active')) {
                closeSearchPanel();
            } else {
                openSearchPanel();
            }
        });
    }

    // Close search panel when clicking on main content
    const mainContentEl = document.querySelector('.main-content');
    if (mainContentEl) {
        mainContentEl.addEventListener('click', function(e) {
            if (document.body.classList.contains('search-panel-active')) {
                // Don't close if clicking inside search panel
                if (searchPanel && searchPanel.contains(e.target)) {
                    return;
                }
                // Don't close if clicking on video controls
                if (e.target.closest('.video-nav-btn') || 
                    e.target.closest('.action-item') ||
                    e.target.closest('.video-container')) {
                    return;
                }
                closeSearchPanel();
            }
        });
    }

    if (searchPanel) {
        searchPanel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.body.classList.contains('search-panel-active')) {
            closeSearchPanel();
        }
    });

    function loadSearchHistory() {
        if (!searchHistoryList || !searchHistorySection) return;
        
        searchHistorySection.style.display = 'block';
        
        sendAjaxRequest('puna_tiktok_get_search_history', {})
            .then(data => {
                if (data.success && data.data.history && data.data.history.length > 0) {
                    searchHistoryList.innerHTML = '';
                    data.data.history.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'search-history-item';
                        const icon = document.createElement('img');
                        icon.src = (typeof puna_tiktok_ajax !== 'undefined' ? puna_tiktok_ajax.theme_uri : '') + '/assets/images/icons/history.svg';
                        icon.className = 'icon-img';
                        icon.alt = 'History';
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        li.appendChild(icon);
                        li.appendChild(span);
                        li.addEventListener('click', function() {
                            if (realSearchInput) {
                                realSearchInput.value = item.query || '';
                                submitSearch(item.query || '');
                            }
                        });
                        searchHistoryList.appendChild(li);
                    });
                    if (clearHistoryBtn) clearHistoryBtn.style.display = 'block';
                } else {
                    searchHistoryList.innerHTML = '';
                    if (clearHistoryBtn) clearHistoryBtn.style.display = 'none';
                }
            })
            .catch(error => {});
    }
    
    function loadSearchSuggestions(query) {
        if (!query || query.length < 2) {
            if (searchSuggestionsList) searchSuggestionsList.style.display = 'none';
            if (searchHistorySection) searchHistorySection.style.display = 'block';
            if (searchPopularSection) searchPopularSection.style.display = 'block';
            return;
        }
        
        if (searchSuggestionsList) searchSuggestionsList.style.display = 'none';
        if (searchHistorySection) searchHistorySection.style.display = 'none';
        if (searchPopularSection) searchPopularSection.style.display = 'none';
        
        sendAjaxRequest('puna_tiktok_search_suggestions', { query: query })
            .then(data => {
                if (data.success && data.data.suggestions && data.data.suggestions.length > 0) {
                    if (searchSuggestionsList) {
                        searchSuggestionsList.innerHTML = '';
                        data.data.suggestions.forEach(function(suggestion) {
                            const li = document.createElement('li');
                            li.className = 'search-suggestion-item';
                            
                            // Add search icon
                            const icon = document.createElement('img');
                            icon.src = (typeof puna_tiktok_ajax !== 'undefined' ? puna_tiktok_ajax.theme_uri : '') + '/assets/images/icons/search.svg';
                            icon.className = 'icon-svg';
                            icon.alt = 'Search';
                            li.appendChild(icon);
                            
                            const span = document.createElement('span');
                            span.textContent = suggestion.text || '';
                            li.appendChild(span);
                            li.addEventListener('click', function() {
                                if (realSearchInput) {
                                    realSearchInput.value = suggestion.text || '';
                                    submitSearch(suggestion.text || '');
                                }
                            });
                            searchSuggestionsList.appendChild(li);
                        });
                        searchSuggestionsList.style.display = 'block';
                    }
                    if (searchHistorySection) searchHistorySection.style.display = 'none';
                    if (searchPopularSection) searchPopularSection.style.display = 'none';
                } else {
                    if (searchSuggestionsList) searchSuggestionsList.style.display = 'none';
                    if (searchHistorySection) searchHistorySection.style.display = 'block';
                    if (searchPopularSection) searchPopularSection.style.display = 'block';
                }
            })
            .catch(error => {
                // Error handling
            });
    }
    
    function submitSearch(query) {
        if (!query || !query.trim()) return;
        
        sendAjaxRequest('puna_tiktok_save_search', { query: query.trim() })
            .then(data => {
                const searchForm = document.getElementById('search-form');
                if (searchForm) {
                    window.location.href = searchForm.action + '?s=' + encodeURIComponent(query.trim());
                }
            })
            .catch(error => {
                const searchForm = document.getElementById('search-form');
                if (searchForm) {
                    window.location.href = searchForm.action + '?s=' + encodeURIComponent(query.trim());
                }
            });
    }
    
    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('Are you sure you want to clear all search history?')) {
                sendAjaxRequest('puna_tiktok_clear_search_history', {})
                    .then(data => {
                        if (data.success) {
                            if (searchHistoryList) searchHistoryList.innerHTML = '';
                            if (clearHistoryBtn) clearHistoryBtn.style.display = 'none';
                        }
                    })
                    .catch(error => {});
            }
        });
    }
    
    if (realSearchInput) {
        realSearchInput.addEventListener('focus', function() {
            loadSearchHistory();
        });
        
        realSearchInput.addEventListener('input', function() {
            const query = realSearchInput.value.trim();
            currentSearchQuery = query;
            
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            
            searchDebounceTimer = setTimeout(function() {
                if (query === currentSearchQuery) {
                    loadSearchSuggestions(query);
                }
            }, 300);
        });
    }
    
    const searchForm = document.getElementById('search-form');
    if (searchForm && realSearchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchValue = realSearchInput.value.trim();
            if (searchValue) {
                submitSearch(searchValue);
            }
        });
        
        realSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchValue = realSearchInput.value.trim();
                if (searchValue) {
                    submitSearch(searchValue);
                }
            }
        });
    }
    
    function loadPopularSearches() {
        const popularList = document.getElementById('search-popular-list');
        if (!popularList) return;
        
        sendAjaxRequest('puna_tiktok_get_popular_searches', {})
            .then(data => {
                // Always clear loading state first
                popularList.innerHTML = '';
                
                if (data.success && data.data.popular && data.data.popular.length > 0) {
                    data.data.popular.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'search-popular-item';
                        const icon = document.createElement('img');
                        icon.src = (typeof puna_tiktok_ajax !== 'undefined' ? puna_tiktok_ajax.theme_uri : '') + '/assets/images/icons/fire.svg';
                        icon.className = 'icon-img';
                        icon.alt = 'Popular';
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        li.appendChild(icon);
                        li.appendChild(span);
                        li.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (realSearchInput) {
                                realSearchInput.value = item.query || '';
                                submitSearch(item.query || '');
                            }
                        });
                        popularList.appendChild(li);
                    });
                } else {
                    // Hide section if no popular searches
                    if (searchPopularSection) {
                        searchPopularSection.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                // On error, clear loading and hide section
                popularList.innerHTML = '';
                if (searchPopularSection) {
                    searchPopularSection.style.display = 'none';
                }
            });
    }

    function loadRelatedSearches(currentQuery) {
        const relatedList = document.getElementById('related-searches-list');
        if (!relatedList) return;
        
        if (!currentQuery || currentQuery.trim().length < 2) {
            loadPopularSearchesForSidebar(relatedList);
            return;
        }
        
        sendAjaxRequest('puna_tiktok_get_related_searches', { query: currentQuery.trim() })
            .then(data => {
                relatedList.innerHTML = '';
                
                // Load related searches
                if (data.success && data.data.related && data.data.related.length > 0) {
                    data.data.related.forEach(function(item) {
                        const li = document.createElement('li');
                        const link = document.createElement('a');
                        link.href = window.location.pathname + '?s=' + encodeURIComponent(item.query || '');
                        link.className = 'search-suggestion-link';
                        const icon = document.createElement('img');
                        icon.src = (typeof puna_tiktok_ajax !== 'undefined' ? puna_tiktok_ajax.theme_uri : '') + '/assets/images/icons/fire.svg';
                        icon.className = 'icon-img';
                        icon.alt = 'Related';
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        link.appendChild(icon);
                        link.appendChild(span);
                        li.appendChild(link);
                        relatedList.appendChild(li);
                    });
                }
            })
            .catch(error => {
                // On error, keep empty
                relatedList.innerHTML = '';
            });
    }
    
    function loadPopularSearchesForSidebar(container) {
        sendAjaxRequest('puna_tiktok_get_popular_searches', {})
            .then(data => {
                if (data.success && data.data.popular && data.data.popular.length > 0) {
                    container.innerHTML = '';
                    data.data.popular.forEach(function(item) {
                        const li = document.createElement('li');
                        const link = document.createElement('a');
                        link.href = window.location.pathname + '?s=' + encodeURIComponent(item.query || '');
                        link.className = 'search-suggestion-link';
                        const icon = document.createElement('img');
                        icon.src = (typeof puna_tiktok_ajax !== 'undefined' ? puna_tiktok_ajax.theme_uri : '') + '/assets/images/icons/fire.svg';
                        icon.className = 'icon-img';
                        icon.alt = 'Popular';
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        link.appendChild(icon);
                        link.appendChild(span);
                        li.appendChild(link);
                        container.appendChild(li);
                    });
                } else {
                    container.innerHTML = '';
                }
            })
            .catch(error => {
                container.innerHTML = '';
            });
    }

    const relatedList = document.getElementById('related-searches-list');
    if (relatedList) {
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('s') || '';
        if (searchQuery) {
            loadRelatedSearches(searchQuery);
        } else {
            loadPopularSearchesForSidebar(relatedList);
        }
    }
});

