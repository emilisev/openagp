<div id="form" class="content-card">
    <header><span>Source</span></header>
    <content>
        <form method="POST" action="{{ Request::route()->getName()?? '/agp' }}">
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
                <label for="url">Api Secret</label>
            </div>

            <div class="form-floating mb-3">
                <input id="dates" name="dates" type="text" class="form-control"
                       value="{{ @$formDefault['dates'] }}"/>
                <label for="dates">Période</label>
                <script type="module">$('#dates').daterangepicker({
                        "locale": {
                            "format": "DD/MM/YYYY",
                            "separator": " - ",
                            "applyLabel": "Appliquer",
                            "cancelLabel": "Annuler",
                            "fromLabel": "Du",
                            "toLabel": "Au",
                            "customRangeLabel": "Personnaliser",
                            "weekLabel": "W",
                            "daysOfWeek": [
                                "D",
                                "L",
                                "M",
                                "M",
                                "J",
                                "V",
                                "S"
                            ],
                            "monthNames": [
                                "Janvier",
                                "Février",
                                "Mars",
                                "Avril",
                                "Mai",
                                "Juin",
                                "Juillet",
                                "Août",
                                "Septembre",
                                "Octobre",
                                "Novembre",
                                "Décembre"
                            ],
                            "firstDay": 1
                        },
                        "maxSpan": {
                            "days": 90
                        },
                        "alwaysShowCalendars": true,
                        ranges: {
                            '7 derniers jours': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
                            '14 derniers jours': [moment().subtract(14, 'days'), moment().subtract(1, 'days')],
                            '30 derniers jours': [moment().subtract(30, 'days'), moment().subtract(1, 'days')],
                            '90 derniers jours': [moment().subtract(90, 'days'), moment().subtract(1, 'days')],
                        },
                        @if (!isset($datepickerDefault))
                            "endDate": moment().subtract(1, 'days'),
                            "startDate": moment().subtract(14, 'days'),
                        @endif
                        "maxDate": moment().subtract(1, 'days')
                    });</script>
            </div>

            <button type="submit" class="btn btn-primary">Afficher mon rapport</button>

        </form>
    </content>
</div>
