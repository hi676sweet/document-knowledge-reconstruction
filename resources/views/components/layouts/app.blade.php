<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sourcebook - your knowledge, connected' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('sourcebook.css') }}">
    @livewireStyles
</head>
<body>
    <aside class="sidebar">
        <a class="brand" href="{{ route('library') }}"><span class="brand-icon"><i></i><i></i><i></i></span>sourcebook<span class="brand-period">.</span></a>
        <div class="workspace-switch"><span class="workspace-avatar">N</span><div><strong>Northstar</strong><small>Your demo workspace</small></div><span class="workspace-dot"></span></div>
        <p class="nav-label">WORKSPACE</p>
        <nav class="side-nav" aria-label="Workspace navigation">
            <a href="{{ route('library') }}" @class(['active' => request()->routeIs('library') && request('mode') !== 'ask'])><span class="nav-icon">▦</span>Knowledge library <span class="shortcut">L</span></a>
            <a href="{{ route('library', ['mode' => 'ask']) }}" @class(['active' => request('mode') === 'ask'])><span class="nav-icon">✧</span>Ask your documents</a>
            <a href="{{ route('documents.create') }}" @class(['active' => request()->routeIs('documents.create')])><span class="nav-icon">⊕</span>Add a document</a>
        </nav>
        <div class="sidebar-note"><span class="note-star">✳</span><strong>A little clarity goes a long way.</strong><p>Keep the context. Find the source. Share what you know.</p><a href="{{ route('library', ['mode' => 'ask']) }}">Explore your knowledge <span>↗</span></a></div>
        <div class="sidebar-bottom"><span class="local-dot"></span>Independent reconstruction<small>Fictional documents · November 2026</small></div>
    </aside>
    <div class="app-shell">
        <header class="topbar"><div class="breadcrumb"><span>Workspace</span><span>/</span><strong>{{ request()->routeIs('documents.*') ? 'Documents' : 'Knowledge library' }}</strong></div><div class="topbar-right"><span class="demo-tag">DEMO</span><span class="user-avatar" title="Your isolated demo session">ND</span></div></header>
        <nav class="mobile-nav" aria-label="Mobile navigation"><a href="{{ route('library') }}">Library</a><a href="{{ route('library', ['mode' => 'ask']) }}">Ask documents</a><a href="{{ route('documents.create') }}">+ Add</a></nav>
        <main class="main-content">
            @if (session('success'))<div role="status" class="notice success">✓ <span>{{ session('success') }}</span></div>@endif
            @if ($errors->any())<div role="alert" class="notice error"><strong>A quick check before continuing</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </main>
        <footer class="app-footer"><span>Made for a little more clarity.</span><span>Session-owned demo · No external AI service</span></footer>
    </div>
    @livewireScripts
    <script>
        document.addEventListener('keydown', (event) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                const input = document.getElementById('knowledge-query');
                if (input) { event.preventDefault(); input.focus(); }
            }
        });
    </script>
</body>
</html>
