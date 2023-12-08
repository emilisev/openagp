@isset($times)
    <footer class="menu">
        Temps de génération : {{ $times['total'] }}s dont Nightscout : {{ $times['network'] }}s
    </footer>
@endisset
