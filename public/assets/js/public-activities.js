(function () {
    'use strict';

    const menuButton = document.getElementById('public-menu-button');
    const drawerFrame = document.getElementById('public-drawer-frame');
    const drawerClose = document.getElementById('public-drawer-close');
    const drawerOverlay = document.getElementById('public-drawer-overlay');

    function setDrawer(open) {
        if (!drawerFrame || !menuButton) return;
        drawerFrame.classList.toggle('is-open', open);
        drawerFrame.setAttribute('aria-hidden', String(!open));
        menuButton.setAttribute('aria-expanded', String(open));
    }

    menuButton?.addEventListener('click', function () { setDrawer(true); });
    drawerClose?.addEventListener('click', function () { setDrawer(false); });
    drawerOverlay?.addEventListener('click', function () { setDrawer(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setDrawer(false);
    });

    const rawActivities = window.TFC_PUBLIC_ACTIVITIES;
    const rawCategories = window.TFC_PUBLIC_CATEGORIES;
    if (!Array.isArray(rawActivities)) return;

    const activities = rawActivities.map(normalizeActivity);
    const state = { category: 'all' };
    const categoryBar = document.getElementById('category-bar');
    const pageState = document.getElementById('page-state');
    const searchScreen = document.getElementById('search-screen');
    const searchInput = document.getElementById('activity-search');
    const searchResults = document.getElementById('search-results');

    const categoryIconPaths = {
        leaf: '<path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        sprout: '<path d="M7 20h10"/><path d="M12 20V9"/><path d="M12 9C12 6 9.5 3.5 5 3.5c0 4.5 2.5 7 7 7z"/><path d="M12 12c0-2.5 2-5 6-5 0 3.5-2.5 5.5-6 5.5z"/>',
        food: '<path d="M3 2v7a2 2 0 002 2h1a2 2 0 002-2V2"/><path d="M5.5 2v20"/><path d="M21 14V2a5 5 0 00-4 4.9V12a2 2 0 002 2h2zm0 0v8"/>',
        coffee: '<path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4z"/><path d="M6 2v3M10 2v3M14 2v3"/>',
        craft: '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/>',
        tool: '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>',
        heart: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0016.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 002 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/>',
        users: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
        book: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
        flask: '<path d="M9 3h6M10 3v6.5L4.6 18.3A2 2 0 006.3 21h11.4a2 2 0 001.7-2.7L14 9.5V3"/><path d="M7 15h10"/>',
        sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        droplet: '<path d="M12 2.7l5.66 5.65a8 8 0 11-11.31 0z"/>',
        home: '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21v-7h6v7"/>',
        camera: '<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>',
        music: '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        star: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14l-5-4.87 6.91-1.01z"/>'
    };

    function categoryIcon(iconName) {
        const path = categoryIconPaths[String(iconName || '').toLowerCase()] || categoryIconPaths.leaf;
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>';
    }

    function renderCategoryBar() {
        if (!categoryBar) return;
        const allIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>';
        const categories = Array.isArray(rawCategories) ? rawCategories : [];
        categoryBar.innerHTML = '<button type="button" class="category-button is-active" data-category="all" aria-pressed="true"><span class="category-icon">' + allIcon + '</span><span>All</span></button>' +
            categories.map(function (category) {
                const name = String(category.name || '').trim().toUpperCase();
                if (!name) return '';
                return '<button type="button" class="category-button" data-category="' + escapeHtml(name) + '" aria-pressed="false"><span class="category-icon">' + categoryIcon(category.icon) + '</span><span>' + escapeHtml(name) + '</span></button>';
            }).join('');
    }

    function normalizeActivity(item) {
        return {
            ...item,
            category: String(item.category || '').trim().toUpperCase(),
            type: String(item.type || '').trim(),
            sortOrder: Number(item.sortOrder || 0),
            isFeatured: Boolean(item.isFeatured),
            image: item.image || '/assets/images/logo-farm.png',
            priceLabel: item.priceLabel || (item.isFree ? 'เข้าร่วมฟรี' : Number(item.fee || 0).toLocaleString('th-TH') + ' บาท / ท่าน')
        };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function detailUrl(item) {
        return '/activities/' + encodeURIComponent(item.code);
    }

    function matchesCategory(item) {
        return state.category === 'all' || item.category === state.category;
    }

    function isEvent(item) {
        return item.type === 'อีเว้นท์';
    }

    function isNews(item) {
        return item.type === 'ข่าวสาร';
    }

    function isActivity(item) {
        return !isEvent(item) && !isNews(item);
    }

    function icon(name) {
        const paths = {
            calendar: '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
            clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            pin: '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/>'
        };
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">' + paths[name] + '</svg>';
    }

    /* แยก "วันที่ · เวลา" ที่เซิร์ฟเวอร์ประกอบมาแล้ว ออกเป็นสองส่วนสำหรับการ์ดแคบ
       ไม่มีตัวคั่นก็ถือว่าทั้งก้อนเป็นวันที่ */
    function splitSchedule(item) {
        const label = String(item.scheduleLabel || '').trim();
        const at = label.indexOf(' · ');
        if (at === -1) return { date: label || '-', time: '' };
        return { date: label.slice(0, at), time: label.slice(at + 3) };
    }

    /* ป้ายราคาบนการ์ด/สไลด์แคบ — ตัด "/ ท่าน" ออก เหลือแค่ตัวเลขให้กระชับ
       ราคาแบบเต็มพร้อมหน่วยยังใช้ item.priceLabel ตรง ๆ ในหน้ารายละเอียด/ลงทะเบียน */
    function shortPrice(item) {
        return String(item.priceLabel || '').replace(/\s*\/\s*ท่าน\s*$/, '');
    }

    function promoSlide(item) {
        return '<a class="promo-slide' + (isEvent(item) ? ' is-event' : '') + '" href="' + detailUrl(item) + '">' +
            '<img src="' + escapeHtml(item.image) + '" alt="">' +
            '<div class="promo-content">' +
                /* ไม่แสดงป้ายหมวดหมู่บนสไลด์ — ผู้ใช้กรองหมวดจากแถบด้านบนอยู่แล้ว
                   ป้ายซ้ำอีกจุดบนรูปเลยเป็นข้อมูลที่ไม่ได้ใช้และบังภาพ */
                '<p class="promo-title">' + escapeHtml(item.title) + '</p>' +
                /* ไม่แสดงสถานที่ในหน้ารายการ — ดูได้ในหน้ารายละเอียด
                   หน้านี้ต้องการแค่ "อะไร เมื่อไหร่ ราคาเท่าไหร่" พอให้ตัดสินใจกดเข้าไปดู */
                /* วันที่กับเวลาแยกคนละบรรทัด — ต่อกันด้วย " · " บนการ์ดแคบแล้วบรรทัดยาวเกิน
                   จนตัดคำกลางคัน อ่านวันที่ไม่จบก่อนขึ้นบรรทัดใหม่ (เกณฑ์เดียวกับการ์ดล่างที่แยกอยู่แล้ว) */
                '<span class="promo-meta">' + icon('calendar') + escapeHtml(splitSchedule(item).date) + '</span>' +
                (splitSchedule(item).time
                    ? '<span class="promo-meta">' + icon('clock') + escapeHtml(splitSchedule(item).time) + '</span>'
                    : '') +
                '<span class="promo-meta promo-price">฿ ' + escapeHtml(shortPrice(item)) + '</span>' +
            '</div>' +
        '</a>';
    }

    /* ข่าวสารไม่มีวัน/เวลา/ราคาให้ตัดสินใจแบบกิจกรรม — แค่ภาพ ชื่อเรื่อง คำอธิบายสั้น ๆ ก็พอ */
    function newsCard(item) {
        return '<a class="other-card is-news" href="' + detailUrl(item) + '">' +
            '<div class="other-thumb"><img src="' + escapeHtml(item.image) + '" alt=""></div>' +
            '<div class="other-body">' +
                '<p class="other-title">' + escapeHtml(item.title) + '</p>' +
                '<p class="other-desc">' + escapeHtml(item.description || '') + '</p>' +
            '</div>' +
        '</a>';
    }

    function activityCard(item) {
        if (isNews(item)) return newsCard(item);

        return '<a class="other-card" href="' + detailUrl(item) + '">' +
            /* ไม่มีป้ายหมวดหมู่บนรูปการ์ด — เหตุผลเดียวกับสไลด์ คือกรองหมวดจากแถบด้านบนอยู่แล้ว */
            '<div class="other-thumb"><img src="' + escapeHtml(item.image) + '" alt=""></div>' +
            '<div class="other-body">' +
                '<p class="other-title">' + escapeHtml(item.title) + '</p>' +
                /* การ์ดแคบลงเหลือสามคอลัมน์ จึงแยกวันกับเวลาเป็นคนละบรรทัด
                   ถ้ารวมเป็นบรรทัดเดียวจะถูกตัดกลางคันจนอ่านเวลาไม่ออก */
                '<div class="other-meta">' + icon('calendar') + '<span>' + escapeHtml(splitSchedule(item).date) + '</span></div>' +
                (splitSchedule(item).time
                    ? '<div class="other-meta">' + icon('clock') + '<span>' + escapeHtml(splitSchedule(item).time) + '</span></div>'
                    : '') +
                '<div class="other-price">฿ ' + escapeHtml(item.priceLabel) + '</div>' +
            '</div>' +
        '</a>';
    }

    function bindSwipe(viewport, track, count, onChange) {
        if (!viewport || !track || count < 2) return;
        let startX = null;
        let startY = null;
        let pointerId = null;
        let index = 0;
        let isSwiping = false;
        let suppressClick = false;

        function show(next) {
            index = Math.max(0, Math.min(count - 1, next));
            track.style.transform = 'translateX(-' + (index * 100) + '%)';
            onChange(index);
        }

        viewport.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            suppressClick = false;
            startX = event.clientX;
            startY = event.clientY;
            pointerId = event.pointerId;
            isSwiping = false;
        });

        viewport.addEventListener('pointermove', function (event) {
            if (startX === null || event.pointerId !== pointerId || isSwiping) return;
            const distanceX = Math.abs(event.clientX - startX);
            const distanceY = Math.abs(event.clientY - startY);
            if (distanceX > 8 && distanceX > distanceY) {
                isSwiping = true;
                track.classList.add('is-dragging');
                viewport.setPointerCapture?.(event.pointerId);
            }
        });

        viewport.addEventListener('pointerup', function (event) {
            if (startX === null || event.pointerId !== pointerId) return;
            const distance = event.clientX - startX;
            if (isSwiping && Math.abs(distance) > 40) {
                suppressClick = true;
                show(index + (distance < 0 ? 1 : -1));
                window.setTimeout(function () { suppressClick = false; }, 0);
            }
            startX = null;
            startY = null;
            pointerId = null;
            isSwiping = false;
            track.classList.remove('is-dragging');
        });

        viewport.addEventListener('pointercancel', function () {
            startX = null;
            startY = null;
            pointerId = null;
            isSwiping = false;
            track.classList.remove('is-dragging');
        });

        viewport.addEventListener('click', function (event) {
            if (!suppressClick) return;
            event.preventDefault();
            event.stopPropagation();
            suppressClick = false;
        }, true);

        return show;
    }

    function renderDots(element, count, onSelect) {
        if (!element) return [];
        element.hidden = count < 2;
        element.innerHTML = count < 2 ? '' : Array.from({ length: count }, function (_, index) {
            return '<button type="button" class="carousel-dot' + (index === 0 ? ' is-active' : '') + '" data-index="' + index + '" aria-label="สไลด์ ' + (index + 1) + '"></button>';
        }).join('');
        const dots = Array.from(element.querySelectorAll('.carousel-dot'));
        element.addEventListener('click', function (event) {
            const dot = event.target.closest('.carousel-dot');
            if (dot) onSelect(Number(dot.dataset.index));
        });
        return dots;
    }

    function renderPromo(sectionId, viewportId, dotsId, items) {
        const section = document.getElementById(sectionId);
        const viewport = document.getElementById(viewportId);
        const dotsElement = document.getElementById(dotsId);
        section.hidden = items.length === 0;
        if (!items.length) {
            viewport.innerHTML = '';
            dotsElement.innerHTML = '';
            return;
        }

        viewport.innerHTML = '<div class="promo-track">' + items.map(promoSlide).join('') + '</div>';
        const track = viewport.querySelector('.promo-track');
        let show = function () {};
        const dots = renderDots(dotsElement, items.length, function (index) { show(index); });
        show = bindSwipe(viewport, track, items.length, function (index) {
            dots.forEach(function (dot, dotIndex) { dot.classList.toggle('is-active', dotIndex === index); });
        }) || show;
    }

    function renderOther(items) {
        const section = document.getElementById('other-activities-section');
        const viewport = document.getElementById('other-activities-carousel');
        const track = document.getElementById('other-activities-track');
        const dotsElement = document.getElementById('other-activities-dots');
        section.hidden = items.length === 0;
        if (!items.length) {
            track.innerHTML = '';
            dotsElement.innerHTML = '';
            return;
        }

        const pages = [];
        /* สามใบต่อหน้า ให้ตรงกับกริดสามคอลัมน์ของ .other-page */
        for (let index = 0; index < items.length; index += 3) pages.push(items.slice(index, index + 3));
        track.innerHTML = pages.map(function (page) {
            return '<div class="other-page">' + page.map(activityCard).join('') + '</div>';
        }).join('');
        let show = function () {};
        const dots = renderDots(dotsElement, pages.length, function (index) { show(index); });
        show = bindSwipe(viewport, track, pages.length, function (index) {
            dots.forEach(function (dot, dotIndex) { dot.classList.toggle('is-active', dotIndex === index); });
        }) || show;
    }

    function recommendationItems() {
        return activities.filter(isActivity).slice(0, 4);
    }

    function emptyState(message) {
        const recommendations = recommendationItems();
        return '<div class="empty-icon"><svg viewBox="0 0 96 96" fill="none" stroke="currentColor"><rect x="20" y="28" width="56" height="48" rx="8"/><path d="M31 18v20M65 18v20M20 43h56M34 57h12M53 57h9M34 67h9"/></svg></div>' +
            '<p class="empty-title">' + escapeHtml(message) + '</p>' +
            (recommendations.length ? '<div class="recommendation-block"><h2 class="section-heading">กิจกรรมแนะนำสำหรับคุณ</h2><div class="recommendation-grid">' + recommendations.map(activityCard).join('') + '</div></div>' : '');
    }

    function renderSections() {
        const matched = activities.filter(matchesCategory);
        const featuredActivities = matched.filter(function (item) { return isActivity(item) && item.isFeatured; });
        const featuredEvents = matched.filter(function (item) { return isEvent(item) && item.isFeatured; });
        const news = matched.filter(isNews);
        const hasResults = featuredActivities.length || featuredEvents.length || news.length;

        renderPromo('featured-activities-section', 'featured-activities-carousel', 'featured-activities-dots', featuredActivities);
        renderPromo('featured-events-section', 'featured-events-carousel', 'featured-events-dots', featuredEvents);
        renderOther(news);

        pageState.hidden = Boolean(hasResults);
        pageState.innerHTML = hasResults ? '' : emptyState(activities.length ? 'ไม่พบกิจกรรมในหมวดหมู่นี้' : 'ยังไม่มีกิจกรรมที่เผยแพร่');
    }

    categoryBar?.addEventListener('click', function (event) {
        const button = event.target.closest('.category-button');
        if (!button) return;
        state.category = button.dataset.category;
        categoryBar.querySelectorAll('.category-button').forEach(function (item) {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        renderSections();
    });

    function renderSearch(query) {
        const value = query.trim().toLowerCase();
        const matched = activities.filter(function (item) {
            return [item.title, item.description, item.category, item.type, item.location]
                .join(' ')
                .toLowerCase()
                .includes(value);
        });
        if (!matched.length) {
            searchResults.innerHTML = '<div class="page-state">' + emptyState('ไม่พบกิจกรรมที่คุณค้นหา') + '</div>';
            return;
        }
        searchResults.innerHTML = matched.map(function (item) {
            return '<a class="search-result" href="' + detailUrl(item) + '"><img src="' + escapeHtml(item.image) + '" alt=""><div class="search-result-body"><p class="search-result-title">' + escapeHtml(item.title) + '</p><p class="search-result-date">' + escapeHtml(item.scheduleLabel || '-') + '</p></div></a>';
        }).join('');
    }

    /* ---------- รายการทั้งหมดของแต่ละหัวข้อ (ปุ่ม All) ----------
       เงื่อนไขของแต่ละชุดต้องตรงกับที่ใช้สร้างหัวข้อนั้นบนหน้าแรก
       ไม่งั้นกด All แล้วได้รายการคนละชุดกับที่เพิ่งเห็นในสไลด์ */
    const LIST_KINDS = {
        activities: { title: 'Activities', match: isActivity },
        events: { title: 'Events', match: isEvent },
        news: { title: 'News & Updates', match: isNews }
    };

    const listScreen = document.getElementById('list-screen');
    const listTitle = document.getElementById('list-title');
    const listResults = document.getElementById('list-results');

    function openList(kind) {
        const config = LIST_KINDS[kind];
        if (!config || !listScreen) return;

        /* ไม่กรองด้วยหมวดหมู่ที่เลือกอยู่ — ปุ่มนี้คือ "ดูทั้งหมดของประเภทนี้" ตามที่ป้ายบอก */
        const items = activities.filter(config.match);

        listTitle.textContent = config.title;
        listResults.innerHTML = items.length
            ? items.map(activityCard).join('')
            : '<div class="page-state">' + emptyState('ยังไม่มีรายการในหัวข้อนี้') + '</div>';

        listScreen.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeList() {
        if (!listScreen) return;
        listScreen.hidden = true;
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.section-all');
        if (trigger) return openList(trigger.dataset.list);
        if (event.target.closest('#list-back')) closeList();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && listScreen && !listScreen.hidden) closeList();
    });

    function openSearch() {
        searchScreen.hidden = false;
        document.body.style.overflow = 'hidden';
        renderSearch('');
        window.setTimeout(function () { searchInput?.focus(); }, 0);
    }

    function closeSearch() {
        searchScreen.hidden = true;
        document.body.style.overflow = '';
    }

    document.getElementById('public-search-button')?.addEventListener('click', openSearch);
    document.getElementById('search-back')?.addEventListener('click', closeSearch);
    searchInput?.addEventListener('input', function () { renderSearch(searchInput.value); });

    renderCategoryBar();
    renderSections();
    if (new URLSearchParams(window.location.search).has('search')) openSearch();
})();
