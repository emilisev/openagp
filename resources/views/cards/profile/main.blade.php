<header class="card-title">
    {{ $profile['profile']?? __("Profil d'insuline") }}
    - {{ date('d/m/Y H:i:s',  $time/\App\Models\DiabetesData::__1SECOND) }}
</header>
<content class="card-body">
    <div class="row justify-content-center">
        <div class="col-auto">{{ __("Ratios") }}
            <div id="ratio-{{$profile['identifier']}}"></div>
            <x-miniDailyGraph renderTo="ratio-{{$profile['identifier']}}" :data="$profile['profileJson']['carbratio']"/>
        </div>
        <div class="col-auto">{{ __("Sensibilité") }}
            <div id="sens-{{$profile['identifier']}}"></div>
            <x-miniDailyGraph renderTo="sens-{{$profile['identifier']}}" :data="$profile['profileJson']['sens']"/>
        </div>
        <div class="col-auto">{{ __("Basale") }}
            <div id="basal-{{$profile['identifier']}}"></div>
            <x-miniDailyGraph renderTo="basal-{{$profile['identifier']}}" :data="$profile['profileJson']['basal']"/>
        </div>
        <div class="col-auto">{{ __("Cible") }}
            <div id="target-{{$profile['identifier']}}"></div>
            <x-miniDailyGraph renderTo="target-{{$profile['identifier']}}" :data="$profile['profileJson']['target_low']"/>
        </div>
    </div>
   {{-- <pre>@php
            var_dump(@$profile);
        @endphp</pre>--}}

</content>
