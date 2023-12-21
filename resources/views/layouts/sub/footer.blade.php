@isset($times)
    <footer class="menu">
        {{ __("Temps de génération : :totals dont Nightscout : :nss", ['total' => $times['total'], 'ns' => $times['network']]) }}
    </footer>
@endisset
