@php
    use Jenssegers\Agent\Agent;
    $agent = new Agent();

$formAction = Request::route()->getName()??($agent->isMobile()?'/daily':'/agp');
if(strpos($formAction, 'generated') === 0) {
	$formAction = ($agent->isMobile()?'/daily':'/agp');
}
@endphp
<div id="form" class="card">
    <header><span>Source</span></header>
    <content>
        <form method="POST" action="{{ $formAction  }}">
            @csrf
            <div class="form-floating mb-3">
                <input id="url" name="url"
                       type="url"
                       class="form-control"
                       placeholder="https://USERNAME.my.nightscoutpro.com/"
                       value="{{ @$formDefault['url'] }}">
                <label for="url">Nightscout URL</label>
                <small id="urlHelp" class="form-text text-muted">Exemple : https://USERNAME.my.nightscoutpro.com/</small>
            </div>

            <div class="form-floating mb-3 show-hide-password input-group">
                <input id="apiSecret" name="apiSecret"
                       type="password"
                       class="form-control"
                       placeholder="password"
                       value="{{ @$formDefault['apiSecret'] }}">
                <div class="input-group-text">
                    <a href=""><i class="bi bi-eye-slash" aria-hidden="true"></i></a>
                </div>
                <label for="url">Api Secret ou Token</label>
            </div>

            <button type="submit" class="btn btn-primary">Afficher mon rapport</button>

        </form>
    </content>
</div>
