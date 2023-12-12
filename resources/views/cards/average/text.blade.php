@php /** @var App\Models\DiabetesData $data */ @endphp
<table>
    <tbody>
    <tr class="average-glucose-row">
        <td class="average-glucose_title">Glucose moyen<br>
            <div class="goal d-none d-lg-block">Objectif&nbsp;: &lt;154 mg/dL</div>
        </td>
        <td>
            <span class="average-glucose-value value">{{$data->getAverage()}}</span>
            <span class="average-glucose-unit">mg/dL</span>
        </td>
    </tr>
    <tr class="gmi-row">
        <td class="gmi_title">GMI<br>
            <div class="goal d-none d-lg-block">Objectif&nbsp;: &lt;7&nbsp;%</div>
        </td>
        <td class="gmi_value value">{{$data->getGmi()}}&nbsp;%</td>
    </tr>
    <tr class="percentCV-row">
        <td class="percentCV_title">Coefficient de variation<br>
            <div class="goal d-none d-lg-block">Objectif&nbsp;: &lt;36&nbsp;%</div>
        </td>
        <td class="percentCV_value value">{{round(($data->getVariation()*10))/10}}&nbsp;%</td>
    </tr>
    <tr class="cgmTimeActive-row">
        <td class="cgmTimeActive_title">Durée MCG actif</td>
        <td class="cgmTimeActive_value value">{{round(($data->getCgmActivePercent()*10))/10}}&nbsp;%</td>
    </tr>
    </tbody>
</table>
