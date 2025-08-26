(function(){
  const WRAP_ID = 'careers';
  const ENABLE_FILTERS = true; // Set to true to enable filters, false to disable

  function waitForAny(selector, root=document, timeout=20000){
    return new Promise((resolve, reject)=>{
      try {
        const found = root.querySelectorAll(selector);
        if (found && found.length) return resolve(found);
      } catch(e){}
      const to = setTimeout(()=>{ try{obs.disconnect();}catch(e){}; reject('timeout'); }, timeout);
      const obs = new MutationObserver(()=>{
        try {
          const list = root.querySelectorAll(selector);
          if (list && list.length){ clearTimeout(to); obs.disconnect(); resolve(list); }
        } catch(e){}
      });
      obs.observe(root, {childList:true, subtree:true});
    });
  }

  function q(sel, scope){ try { return (scope||document).querySelector(sel); } catch(e){ return null; } }
  function hasGoodHref(a){
    if (!a) return false;
    const href = (a.getAttribute('href') || a.getAttribute('data-url') || a.getAttribute('data-href') || '').trim();
    if (!href || href === '#' || href.startsWith('javascript')) return false;
    return true;
  }
  function absoluteUrl(url){
    try { return new URL(url, window.location.origin).toString(); } catch(e){ return null; }
  }
  function triggerSequence(el){
    if (!el) return;
    ['mousedown','mouseup','click'].forEach(type=>{
      try { el.dispatchEvent(new MouseEvent(type, {bubbles:true, cancelable:true, view:window})); } catch(e){}
    });
  }

  // Enhanced filter functions for new shortcode
  function setupComeetFilters() {
    const filterBtns = document.querySelectorAll('.comeet-filter-btn');
    const sliderContainer = document.querySelector('.comeet-job-slider');
    
    if (!filterBtns.length || !sliderContainer) return;
    
    console.log('[Comeet Slider] Setting up filter handlers for', filterBtns.length, 'buttons');
    
    filterBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const category = this.getAttribute('data-category');
        console.log('[Comeet Slider] Filter clicked:', category);
        
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Filter slides
        const slides = sliderContainer.querySelectorAll('.swiper-slide');
        let visibleCount = 0;
        
        slides.forEach(slide => {
          const slideCategory = slide.getAttribute('data-category');
          const shouldShow = category === 'all' || slideCategory === category;
          
          if (shouldShow) {
            slide.style.display = 'block';
            visibleCount++;
          } else {
            slide.style.display = 'none';
          }
        });
        
        console.log('[Comeet Slider] Showing', visibleCount, 'slides for category:', category);
        
        // Refresh swiper if it exists
        if (window.comeetSwiper) {
          window.comeetSwiper.update();
          window.comeetSwiper.slideTo(0);
        }
      });
    });
  }

  // Legacy filter functions (for backward compatibility)
  function createFilterButtons(categories) {
    const filterContainer = document.createElement('div');
    filterContainer.className = 'job-filters';
    filterContainer.innerHTML = `
      <button class="filter-btn active" data-filter="all">הכל</button>
      ${categories.map(cat => `<button class="filter-btn" data-filter="${cat}">${cat}</button>`).join('')}
    `;
    return filterContainer;
  }

  function setupFilterHandlers(swiper) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        
        // Update active state
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Filter slides
        document.querySelectorAll('.swiper-slide').forEach(slide => {
          if (filter === 'all' || slide.dataset.category === filter) {
            slide.style.display = '';
          } else {
            slide.style.display = 'none';
          }
        });
        
        // Update slider layout
        swiper.update();
      });
    });
  }

  // English content handling for position pages
  function handleEnglishContent() {
    console.log('[Comeet Slider] Checking for English content...');
    
    // Try multiple selectors for "About The Position"
    const selectors = [
      'h4.comeet-position-description',
      'h4:contains("About The Position")',
      'h3:contains("About The Position")',
      'h2:contains("About The Position")',
      '*:contains("About The Position")'
    ];
    
    let aboutPositionEl = null;
    
    // Try each selector
    for (const selector of selectors) {
      try {
        if (selector.includes(':contains')) {
          // Manual search for text content
          const elements = document.querySelectorAll(selector.split(':')[0]);
          for (const el of elements) {
            if (el.textContent && el.textContent.includes('About The Position')) {
              aboutPositionEl = el;
              console.log('[Comeet Slider] Found "About The Position" in:', el.tagName, el.className);
              break;
            }
          }
        } else {
          const el = document.querySelector(selector);
          if (el && el.textContent && el.textContent.includes('About The Position')) {
            aboutPositionEl = el;
            console.log('[Comeet Slider] Found "About The Position" in:', el.tagName, el.className);
            break;
          }
        }
        if (aboutPositionEl) break;
      } catch (e) {
        console.log('[Comeet Slider] Selector failed:', selector, e);
      }
    }
    
    if (!aboutPositionEl) {
      // Fallback: search all elements for "About The Position"
      const allElements = document.querySelectorAll('*');
      for (const el of allElements) {
        if (el.textContent && el.textContent.trim() === 'About The Position') {
          aboutPositionEl = el;
          console.log('[Comeet Slider] Found "About The Position" via fallback in:', el.tagName, el.className);
          break;
        }
      }
    }
    
    if (aboutPositionEl) {
      console.log('[Comeet Slider] Processing English content...');
      
      // Find the container - try multiple approaches
      let positionContainer = aboutPositionEl.closest('.comeet-position-info, .position-content, .comeet-content, .entry-content, main, article, .content');
      
      if (!positionContainer) {
        // Try parent elements up the tree
        let current = aboutPositionEl.parentElement;
        let depth = 0;
        while (current && depth < 5) {
          if (current.tagName === 'MAIN' || current.tagName === 'ARTICLE' || 
              current.classList.contains('content') || current.classList.contains('entry-content')) {
            positionContainer = current;
            break;
          }
          current = current.parentElement;
          depth++;
        }
      }
      
      // If still no container, use the body or a large parent
      if (!positionContainer) {
        positionContainer = aboutPositionEl.closest('body') || document.body;
      }
      
      if (positionContainer) {
        console.log('[Comeet Slider] Applying LTR to container:', positionContainer.tagName, positionContainer.className);
        
        // Apply LTR and left-align styles
        positionContainer.style.direction = 'ltr';
        positionContainer.style.textAlign = 'left';
        
        // Also apply to all child elements to ensure consistency
        const allElements = positionContainer.querySelectorAll('*');
        allElements.forEach(el => {
          el.style.direction = 'ltr';
          el.style.textAlign = 'left';
        });
        
        console.log('[Comeet Slider] Applied LTR formatting to', allElements.length, 'elements');
      } else {
        console.log('[Comeet Slider] No suitable container found');
      }
    } else {
      console.log('[Comeet Slider] "About The Position" not found on this page');
    }
  }

  function buildSlideFromItem(item, originalAnchor){
    const titleEl = q('a, .title, [data-title], h3, h2', item) || item;
    const title = (titleEl.textContent||'').replace(/\s{2,}/g,' ').trim();
    const metaEl  = q('.comeet-position-details, .details, .subtitle, .meta', item);
    const meta = metaEl ? metaEl.textContent.trim() : '';

    let href = null;
    if (hasGoodHref(originalAnchor)) {
      const raw = originalAnchor.getAttribute('href') || originalAnchor.getAttribute('data-url') || originalAnchor.getAttribute('data-href');
      href = absoluteUrl(raw);
    }

    const slide = document.createElement('div');
    slide.className = 'swiper-slide';
    slide.innerHTML = `
      <article class="job-card">
        <h3 class="job-title">${title}</h3>
        <div class="job-cta"><a ${href ? `href="${href}" target="_blank" rel="noopener"` : 'href="#"'}>לפרטים והגשת מועמדות</a></div>
      </article>`;

    const cta = slide.querySelector('.job-cta a');
    cta.addEventListener('click', function(ev){
      if (!href){
        ev.preventDefault();
        const a = originalAnchor || q('a', item);
        if (a) return triggerSequence(a);
        triggerSequence(item);
      }
    });
    return slide;
  }

  function initSliderFromRoots(host, roots){
    const pairs = [];
    roots.forEach(root => {
      let list = root.querySelectorAll('.comeet-position, .comeet-position-item, .comeet-list-item, .position, li');
      list.forEach(it => pairs.push({item: it, anchor: q('a[href], [data-url], [data-href]', it)}));
    });
    if (!pairs.length){ console.warn('[Comeet Slider] No job items found'); return; }
    
    // Limit to maximum 18 jobs
    const maxJobs = 18;
    const limitedPairs = pairs.slice(0, maxJobs);

    // Extract categories for filters (if enabled)
    let categories = [];
    if (ENABLE_FILTERS) {
      categories = [...new Set(limitedPairs.map(pair => {
        // Try to extract category from job title
        const title = pair.item.textContent || '';
        if (title.includes('Engineer') || title.includes('Back-End') || title.includes('Front-End')) return 'Engineering';
        if (title.includes('Analyst')) return 'Analysis';
        if (title.includes('Senior')) return 'Senior';
        if (title.includes('Mid-Level')) return 'Mid-Level';
        return 'Other';
      }))].filter(cat => cat !== 'Other');
    }

    const swiperEl = document.createElement('div');
    swiperEl.className = 'swiper';
    const wrap = document.createElement('div');
    wrap.className = 'swiper-wrapper';
    swiperEl.appendChild(wrap);

    // Create slides with category data attributes
    limitedPairs.forEach(({item, anchor}) => {
      const slide = buildSlideFromItem(item, anchor);
      if (ENABLE_FILTERS) {
        const title = item.textContent || '';
        let category = 'Other';
        if (title.includes('Engineer') || title.includes('Back-End') || title.includes('Front-End')) category = 'Engineering';
        else if (title.includes('Analyst')) category = 'Analysis';
        else if (title.includes('Senior')) category = 'Senior';
        else if (title.includes('Mid-Level')) category = 'Mid-Level';
        slide.dataset.category = category;
      }
      wrap.appendChild(slide);
    });

    const nav = document.createElement('div');
    nav.className = 'cr-jobs-nav';
    nav.innerHTML = `
      <button class="cr-jobs-button prev" aria-label="הקודם" type="button"></button>
      <button class="cr-jobs-button next" aria-label="הבא" type="button"></button>
    `;

    // Insert filter buttons if enabled
    if (ENABLE_FILTERS && categories.length > 1) {
      const filterButtons = createFilterButtons(categories);
      host.insertBefore(filterButtons, host.firstChild);
      host.insertBefore(swiperEl, filterButtons.nextSibling);
    } else {
      host.insertBefore(swiperEl, host.firstChild);
    }
    
    host.insertBefore(nav, swiperEl.nextSibling);

    // Hide originals inside their roots
    roots.forEach(root => { root.style.display = 'none'; });

    if (typeof Swiper === 'undefined') { console.warn('Swiper missing'); return; }
    const swiper = new Swiper(swiperEl, {
      rtl: document.documentElement.dir === 'rtl',
      slidesPerView: 1.15,
      spaceBetween: 14,
      grabCursor: true,
      keyboard: { enabled: true },
      navigation: { nextEl: nav.querySelector('.next'), prevEl: nav.querySelector('.prev') },
      breakpoints: {
        480:{slidesPerView:1.25,spaceBetween:18},
        768:{slidesPerView:2.2, spaceBetween:18},
        1024:{slidesPerView:3,  spaceBetween:20},
        1280:{slidesPerView:2.8, spaceBetween:20}, // Hide last 2 jobs at 1280px
        1366:{slidesPerView:3.5,spaceBetween:22}
      }
    });

    // Setup filter handlers if enabled
    if (ENABLE_FILTERS && categories.length > 1) {
      setupFilterHandlers(swiper);
    }
  }

  async function boot(){
    const host = document.getElementById(WRAP_ID);
    if (!host) return;
    const selector = [
      '#'+WRAP_ID+' .comeet-positions',
      '#'+WRAP_ID+' .comeet-widget',
      '#'+WRAP_ID+' .comeet-container',
      '#'+WRAP_ID+' .comeet',
      '#'+WRAP_ID+' ul',
      '#'+WRAP_ID+' ol'
    ].join(', ');
    try {
      const roots = await waitForAny(selector, host, 20000);
      setTimeout(()=> initSliderFromRoots(host, roots), 200);
    } catch(e){
      console.warn('[Comeet Slider] timeout – selector didn’t match', e);
    }
    
    // Handle English content on position pages
    handleEnglishContent();
  }

  // Initialize new shortcode functionality - DISABLED FOR TESTING
  function initComeetShortcode() {
    console.log('[Comeet Slider] Shortcode JS initialization DISABLED for testing');
    return; // Exit early - no JS initialization
    
    // Check if we have the new shortcode container
    const shortcodeContainer = document.querySelector('.cr-jobs-display-container');
    if (!shortcodeContainer) return;
    
    console.log('[Comeet Slider] Initializing shortcode functionality');
    
    // Setup filters
    setupComeetFilters();
    
    // Initialize Swiper for the shortcode
    const swiperEl = shortcodeContainer.querySelector('.cr-jobs-display');
    if (swiperEl && typeof Swiper !== 'undefined') {
      window.comeetSwiper = new Swiper(swiperEl, {
        rtl: document.documentElement.dir === 'rtl',
        slidesPerView: 1.2,
        spaceBetween: 20,
        grabCursor: true,
        keyboard: { enabled: true },
        breakpoints: {
          640: { slidesPerView: 2, spaceBetween: 20 },
          1024: { slidesPerView: 3, spaceBetween: 30 }
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        }
      });
      
      console.log('[Comeet Slider] Swiper initialized for shortcode');
    }
    
    // Handle English content detection
    handleEnglishContent();
  }

  // Main initialization
  function init() {
    // Try legacy initialization first
    boot();
    
    // Then try shortcode initialization
    setTimeout(initComeetShortcode, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();