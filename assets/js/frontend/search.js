/**
 * Search panel functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const searchTrigger = document.getElementById('search-trigger');
    const closeSearchBtn = document.getElementById('close-search');
    const searchPanel = document.getElementById('search-panel');
    const realSearchInput = document.getElementById('real-search-input');
    const searchSuggestionsContainer = document.getElementById('search-suggestions-container');
    const searchSuggestionsList = document.getElementById('search-suggestions-list');
    const searchHistorySection = document.getElementById('search-history-section');
    const searchHistoryList = document.getElementById('search-history-list');
    const searchPopularSection = document.getElementById('search-popular-section');
    const searchLoading = document.getElementById('search-loading');
    const clearHistoryBtn = document.getElementById('clear-history-btn');
    
    let searchDebounceTimer = null;
    let currentSearchQuery = '';
    
    function openSearchPanel() {
        document.body.classList.add('search-panel-active');
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
    }

    if (searchTrigger) {
        searchTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (document.body.classList.contains('search-panel-active')) {
                closeSearchPanel();
            } else {
                openSearchPanel();
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

    const mainContentEl = document.querySelector('.main-content');
    if (mainContentEl) {
        mainContentEl.addEventListener('click', function(e) {
            if (document.body.classList.contains('search-panel-active')) {
                if (!e.target.closest('.video-nav-btn') && 
                    !e.target.closest('.action-item') &&
                    !e.target.closest('.video-container')) {
                    closeSearchPanel();
                }
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
                        
                        // Create icon element safely
                        const iconImg = document.createElement('img');
                        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                        iconImg.src = `${themeUri}/assets/images/icons/search.svg`;
                        iconImg.alt = 'History';
                        iconImg.className = 'icon-svg';
                        li.appendChild(iconImg);
                        
                        // Create span element safely
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
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
                    searchHistoryList.innerHTML = '<li class="search-history-empty">No search history</li>';
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
        
        if (searchLoading) searchLoading.style.display = 'block';
        if (searchSuggestionsList) searchSuggestionsList.style.display = 'none';
        if (searchHistorySection) searchHistorySection.style.display = 'none';
        if (searchPopularSection) searchPopularSection.style.display = 'none';
        
        sendAjaxRequest('puna_tiktok_search_suggestions', { query: query })
            .then(data => {
                if (searchLoading) searchLoading.style.display = 'none';
                
                if (data.success && data.data.suggestions && data.data.suggestions.length > 0) {
                    if (searchSuggestionsList) {
                        searchSuggestionsList.innerHTML = '';
                        data.data.suggestions.forEach(function(suggestion) {
                            const li = document.createElement('li');
                            li.className = 'search-suggestion-item';
                            
                            let iconName = 'search';
                            if (suggestion.type === 'user') {
                                iconName = 'home';
                            } else if (suggestion.type === 'video') {
                                iconName = 'play';
                            } else if (suggestion.type === 'history') {
                                iconName = 'search';
                            }
                            
                            // Create icon element safely
                            const iconImg = document.createElement('img');
                            const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                            iconImg.src = `${themeUri}/assets/images/icons/${iconName}.svg`;
                            iconImg.alt = suggestion.text || '';
                            iconImg.className = 'icon-svg';
                            li.appendChild(iconImg);
                            
                            // Create span element safely
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
                if (searchLoading) searchLoading.style.display = 'none';
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
                            if (searchHistoryList) searchHistoryList.innerHTML = '<li class="search-history-empty">No search history</li>';
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
                if (data.success && data.data.popular && data.data.popular.length > 0) {
                    popularList.innerHTML = '';
                    data.data.popular.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'search-popular-item';
                        
                        // Create icon element safely
                        const iconImg = document.createElement('img');
                        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                        iconImg.src = `${themeUri}/assets/images/icons/fire.svg`;
                        iconImg.alt = item.query || '';
                        iconImg.className = 'icon-svg';
                        li.appendChild(iconImg);
                        
                        // Create span element safely
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        li.appendChild(span);
                        
                        li.style.cursor = 'pointer';
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
                    // No data - hide the section or show empty state
                    if (popularList) {
                        popularList.innerHTML = '<li class="search-popular-empty">No popular searches</li>';
                    }
                    if (searchPopularSection) {
                        searchPopularSection.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                // On error, hide the section
                if (popularList) {
                    popularList.innerHTML = '';
                }
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
                
                // Load related searches first
                let relatedItems = [];
                if (data.success && data.data.related && data.data.related.length > 0) {
                    relatedItems = data.data.related;
                    relatedItems.forEach(function(item) {
                        const li = document.createElement('li');
                        const searchUrl = window.location.pathname + '?s=' + encodeURIComponent(item.query || '');
                        
                        // Create anchor element safely
                        const link = document.createElement('a');
                        link.href = searchUrl;
                        link.className = 'search-suggestion-link';
                        
                        // Create icon element safely
                        const iconImg = document.createElement('img');
                        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                        iconImg.src = `${themeUri}/assets/images/icons/search.svg`;
                        iconImg.alt = item.query || '';
                        iconImg.className = 'icon-svg';
                        link.appendChild(iconImg);
                        
                        // Create span element safely
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        link.appendChild(span);
                        
                        li.appendChild(link);
                        relatedList.appendChild(li);
                    });
                }
                
                // If we have less than 5 items, load popular searches to fill up
                if (relatedItems.length < 5) {
                    sendAjaxRequest('puna_tiktok_get_popular_searches', {})
                        .then(popularData => {
                            if (popularData.success && popularData.data.popular && popularData.data.popular.length > 0) {
                                const existingQueries = new Set(relatedItems.map(item => item.query.toLowerCase()));
                                const currentQueryLower = currentQuery.trim().toLowerCase();
                                let addedCount = 0;
                                
                                popularData.data.popular.forEach(function(item) {
                                    if (addedCount >= (5 - relatedItems.length)) return;
                                    const itemQueryLower = (item.query || '').toLowerCase();
                                    
                                    // Skip if already in related searches or matches current query
                                    if (!existingQueries.has(itemQueryLower) && itemQueryLower !== currentQueryLower) {
                                        const li = document.createElement('li');
                                        const searchUrl = window.location.pathname + '?s=' + encodeURIComponent(item.query || '');
                                        
                                        // Create anchor element safely
                                        const link = document.createElement('a');
                                        link.href = searchUrl;
                                        link.className = 'search-suggestion-link';
                                        
                                        // Create icon element safely
                                        const iconImg = document.createElement('img');
                                        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                                        iconImg.src = `${themeUri}/assets/images/icons/fire.svg`;
                                        iconImg.alt = item.query || '';
                                        iconImg.className = 'icon-svg';
                                        link.appendChild(iconImg);
                                        
                                        // Create span element safely
                                        const span = document.createElement('span');
                                        span.textContent = item.query || '';
                                        link.appendChild(span);
                                        
                                        li.appendChild(link);
                                        relatedList.appendChild(li);
                                        existingQueries.add(itemQueryLower);
                                        addedCount++;
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            // Do nothing, keep what we have
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
                        const searchUrl = window.location.pathname + '?s=' + encodeURIComponent(item.query || '');
                        
                        // Create anchor element safely
                        const link = document.createElement('a');
                        link.href = searchUrl;
                        link.className = 'search-suggestion-link';
                        
                        // Create icon element safely
                        const iconImg = document.createElement('img');
                        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
                        iconImg.src = `${themeUri}/assets/images/icons/fire.svg`;
                        iconImg.alt = item.query || '';
                        iconImg.className = 'icon-svg';
                        link.appendChild(iconImg);
                        
                        // Create span element safely
                        const span = document.createElement('span');
                        span.textContent = item.query || '';
                        link.appendChild(span);
                        
                        li.appendChild(link);
                        container.appendChild(li);
                    });
                } else {
                    // No data, keep empty
                    container.innerHTML = '';
                }
            })
            .catch(error => {
                // On error, keep empty
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

