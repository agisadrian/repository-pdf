@extends('layouts.app')

@section('title', 'Cari Dokumen - Repository PDF')

@section('content')

    <h1 class="page-title">Cari Dokumen</h1>
    <p class="page-subtitle">Cari berdasarkan judul, isi dokumen, atau filter per kategori.</p>

    <form action="{{ url('/cari') }}" method="GET" class="search-filter-form">
        <div class="search-input-wrap">
            <input
                type="text"
                name="q"
                id="search-autocomplete-input"
                value="{{ $keyword }}"
                placeholder="Ketik judul atau isi dokumen..."
                class="search-input"
                autocomplete="off"
                role="combobox"
                aria-expanded="false"
                aria-autocomplete="list"
                aria-controls="search-autocomplete-list"
            >
            <ul id="search-autocomplete-list" class="search-autocomplete-list" role="listbox" hidden></ul>
        </div>

        <select name="category" class="form-input search-filter-select">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <select name="year" class="form-input search-filter-select">
            <option value="">Semua Tahun</option>
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>

        <select name="month" class="form-input search-filter-select">
            <option value="">Semua Bulan</option>
            @foreach ($months as $num => $name)
                <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Cari</button>

        @if ($keyword || $categoryId || $year || $month || $sort !== 'newest')
            <a href="{{ url('/cari') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="search-sort-bar">
        <label for="sort-select">Urutkan:</label>
        <select id="sort-select" class="form-input search-filter-select" onchange="applySorting(this.value)">
            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
            <option value="popular" @selected($sort === 'popular')>Terpopuler</option>
            <option value="title" @selected($sort === 'title')>Judul A-Z</option>
        </select>
    </div>

    <script>
        function applySorting(sort) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }
    </script>

    @if ($keyword || $categoryId || $year || $month)
        <p class="search-result-info">
            @if ($keyword)
                Menampilkan hasil untuk: <strong>"{{ $keyword }}"</strong>
            @endif
            @if ($categoryId)
                {{ $keyword ? 'di kategori' : 'Menampilkan kategori' }}
                <strong>{{ $categories->firstWhere('id', $categoryId)?->name }}</strong>
            @endif
            @if ($month)
                {{ ($keyword || $categoryId) ? 'bulan' : 'Menampilkan bulan' }}
                <strong>{{ $months[$month] }}</strong>
            @endif
            @if ($year)
                {{ ($keyword || $categoryId || $month) ? 'tahun' : 'Menampilkan tahun' }}
                <strong>{{ $year }}</strong>
            @endif
            ({{ $documents->total() }} dokumen ditemukan)
        </p>
    @endif

    @if ($documents->isEmpty())
        <div class="empty-state">
            <p>Tidak ada dokumen ditemukan.</p>
        </div>
    @else
        @foreach ($grouped as $periodLabel => $docsInPeriod)
            @if ($sort === 'newest')
                <h3 class="period-heading">{{ $periodLabel }}</h3>
            @endif

            <div class="document-grid">
                @foreach ($docsInPeriod as $doc)
                    <a href="{{ url('/dokumen/' . $doc->slug) }}" class="document-card">
                        <div class="doc-cover">
                            @if ($doc->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($doc->cover) }}" alt="Sampul dokumen {{ $doc->title }}" loading="lazy">
                            @else
                                <span class="doc-cover-placeholder">{{ substr($doc->title, 0, 1) }}</span>
                            @endif
                            @if ($doc->created_at && $doc->created_at->gt(now()->subDays(7)))
                                <span class="badge-new">Baru</span>
                            @endif
                        </div>
                        <div class="doc-title">{{ $doc->title }}</div>
                        <div class="doc-meta">{{ $doc->author ?? 'Penulis tidak diketahui' }} &middot; {{ $doc->period_label }}</div>
                        @php $snippet = $keyword ? $doc->searchSnippet($keyword) : null; @endphp
                        @if ($snippet)
                            <div class="doc-snippet-wrap">
                                <p class="doc-snippet">{!! $snippet !!}</p>
                                {{-- Muncul cuma pas kartu ini di-hover, dan cuma kalau ada 2+ kata kunci
                                     ke-highlight di snippet ini -- biar bisa loncat antar kemunculan
                                     tanpa ganggu kartu lain --}}
                                <div class="snippet-nav" hidden>
                                    <button type="button" class="snippet-nav-btn snippet-nav-prev" title="Sebelumnya" aria-label="Kata kunci sebelumnya">&#9650;</button>
                                    <span class="snippet-nav-count"></span>
                                    <button type="button" class="snippet-nav-btn snippet-nav-next" title="Berikutnya" aria-label="Kata kunci berikutnya">&#9660;</button>
                                </div>
                            </div>
                        @endif
                        @if ($doc->category)
                            <span class="badge">{{ $doc->category->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="pagination-wrap">
            {{ $documents->links() }}
        </div>
    @endif

    {{-- Navigasi per kartu: kalau snippet sebuah kartu punya 2+ kata kunci ke-highlight,
         munculin panah kecil pas kartu itu di-hover buat gantian nyorot kemunculannya --}}
    <script>
        (function () {
            var wraps = document.querySelectorAll('.doc-snippet-wrap');

            wraps.forEach(function (wrap) {
                var marks = Array.prototype.slice.call(wrap.querySelectorAll('.doc-snippet mark'));
                var nav = wrap.querySelector('.snippet-nav');

                if (!nav) return;

                // Cuma tampilin navigasi kalau kata kuncinya muncul lebih dari 1 kali di snippet ini
                if (marks.length < 2) {
                    nav.remove();
                    return;
                }

                var currentIndex = 0;
                marks[0].classList.add('mark-active');

                var countEl = nav.querySelector('.snippet-nav-count');
                countEl.textContent = '1/' + marks.length;
                nav.hidden = false;

                function goTo(index) {
                    marks[currentIndex].classList.remove('mark-active');
                    currentIndex = (index + marks.length) % marks.length;
                    marks[currentIndex].classList.add('mark-active');
                    countEl.textContent = (currentIndex + 1) + '/' + marks.length;
                    marks[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                // stopPropagation + preventDefault penting: snippet-nav ini duduk di dalam <a>
                // kartu dokumen, jadi tanpa ini klik tombolnya bakal ikut buka halaman dokumen
                nav.querySelector('.snippet-nav-prev').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    goTo(currentIndex - 1);
                });
                nav.querySelector('.snippet-nav-next').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    goTo(currentIndex + 1);
                });
            });
        })();
    </script>

    <script>
        (function () {
            var input = document.getElementById('search-autocomplete-input');
            var list = document.getElementById('search-autocomplete-list');

            if (!input || !list) return;

            var debounceTimer = null;
            var currentController = null;
            var activeIndex = -1;
            var items = [];

            function closeList() {
                list.hidden = true;
                list.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
                items = [];
            }

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str || '';
                return div.innerHTML;
            }

            function renderList(results) {
                items = results;
                activeIndex = -1;

                if (!results.length) {
                    closeList();
                    return;
                }

                list.innerHTML = results.map(function (doc, i) {
                    return '' +
                        '<li role="option" id="ac-item-' + i + '" data-index="' + i + '">' +
                        '<a href="' + doc.url + '" class="ac-item">' +
                        (doc.cover
                            ? '<img src="' + doc.cover + '" alt="" loading="lazy">'
                            : '<span class="ac-item-placeholder">' + escapeHtml(doc.title.charAt(0)) + '</span>') +
                        '<span class="ac-item-text">' +
                        '<span class="ac-item-title">' + escapeHtml(doc.title) + '</span>' +
                        '<span class="ac-item-author">' + escapeHtml(doc.author || 'Penulis tidak diketahui') + '</span>' +
                        '</span>' +
                        '</a>' +
                        '</li>';
                }).join('');

                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function setActive(index) {
                var options = list.querySelectorAll('li');
                options.forEach(function (el) { el.classList.remove('is-active'); });

                if (index >= 0 && index < options.length) {
                    options[index].classList.add('is-active');
                    input.setAttribute('aria-activedescendant', 'ac-item-' + index);
                    activeIndex = index;
                } else {
                    input.removeAttribute('aria-activedescendant');
                    activeIndex = -1;
                }
            }

            function fetchSuggestions(query) {
                if (currentController) {
                    currentController.abort();
                }
                currentController = new AbortController();

                fetch('{{ url('/cari/suggest') }}?q=' + encodeURIComponent(query), {
                    signal: currentController.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (res) { return res.ok ? res.json() : []; })
                    .then(function (data) { renderList(data || []); })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') closeList();
                    });
            }

            input.addEventListener('input', function () {
                var query = input.value.trim();

                clearTimeout(debounceTimer);

                if (query.length < 2) {
                    closeList();
                    return;
                }

                // Debounce 300ms -- nunggu orang berhenti ngetik dulu sebelum nembak request,
                // biar nggak query tiap 1 huruf diketik
                debounceTimer = setTimeout(function () {
                    fetchSuggestions(query);
                }, 300);
            });

            input.addEventListener('keydown', function (e) {
                if (list.hidden) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    setActive(Math.min(activeIndex + 1, items.length - 1));
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    setActive(Math.max(activeIndex - 1, -1));
                } else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && items[activeIndex]) {
                        e.preventDefault();
                        window.location.href = items[activeIndex].url;
                    }
                    // kalau nggak ada item aktif, biarin form submit normal (pencarian biasa)
                } else if (e.key === 'Escape') {
                    closeList();
                }
            });

            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    closeList();
                }
            });
        })();
    </script>

@endsection
