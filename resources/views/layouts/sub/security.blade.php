<div class="card">
    <header><span>Sécurité des données</span></header>
    <content>En utilisant ce site, les données suivantes seront stockées sur le serveur de manière cryptée le temps de votre session :
        <ul>
            <li>URL Nightscout</li>
            <li>API secret</li>
            <li>Données de glycémie et de traitement</li>
        </ul>
        @if (Request::session()->has('url'))
            <form method="POST" action="{{URL::to('/logout') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Effacer mes données</button>
            </form>
        @endif
    </content>
</div>
