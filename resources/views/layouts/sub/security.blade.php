<div class="card">
    <header><span>{{ __("Sécurité des données") }}</span></header>
    <content>{{ __("En utilisant ce site, les données suivantes seront stockées sur le serveur de manière cryptée le temps de votre session :") }}
        <ul>
            <li>{{ __("URL Nightscout") }}</li>
            <li>{{ __("Api Secret ou Token") }}</li>
            <li>{{ __("Données de glycémie et de traitement") }}</li>
        </ul>
        @if (Request::session()->has('url'))
            <form method="POST" action="{{URL::to('/logout') }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __("Effacer mes données") }}</button>
            </form>
        @endif
    </content>
</div>
