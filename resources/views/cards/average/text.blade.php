@php /** @var App\Models\DiabetesData $data */ @endphp
<table>
    <tbody>
    <tr class="average-glucose-row">
        <td class="average-glucose_title">{{ __("Glucose moyen") }}<br>
            <div class="goal d-none d-lg-block">{{ __("Objectif : <:val mg/dL", ['val' => 154]) }}</div>
        </td>
        <td>
            <span class="average-glucose-value value">{{$data->getAverage()}}</span>
            <span class="average-glucose-unit">{{ __("mg/dL") }}</span>
        </td>
    </tr>
    <tr class="gmi-row">
        <td class="gmi_title">{{ __("HbA1c estimée") }}<br>
            <div class="goal d-none d-lg-block">{{ __("Objectif : <:val %", ['val' => 7]) }}</div>
        </td>
        <td class="gmi_value value">{{$data->getGmi()}}&nbsp;%</td>
    </tr>
    <tr class="percentCV-row">
        <td class="percentCV_title">{{ __("Coefficient de variation") }}<br>
            <div class="goal d-none d-lg-block">{{ __("Objectif : <:val %", ['val' => 36]) }}</div>
        </td>
        <td class="percentCV_value value">{{round(($data->getVariation()*10))/10}}&nbsp;%</td>
    </tr>
    <tr class="cgmTimeActive-row">
        <td class="cgmTimeActive_title">{{ __("Durée MCG actif") }}</td>
        <td class="cgmTimeActive_value value">{{round(($data->getCgmActivePercent()*10))/10}}&nbsp;%</td>
    </tr>
    </tbody>
</table>
