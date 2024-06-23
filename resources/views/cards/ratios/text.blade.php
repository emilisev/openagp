@foreach (config('diabetes.lunchTypes') as $lunchType)
    @if($lunchType != 'night' && array_key_exists($lunchType, $data) && is_array($data[$lunchType]))
    <div class="col-auto text-center">
        <div class="card m-0 p-2 text-center" style="background-color: {{''}}">
            <p class="display-3 m-0">{{$data[$lunchType][0]['y']}}</p>
            <span>{{$data[$lunchType][0]['insulin']}}U:{{$data[$lunchType][0]['carbs']}}g</span>
        </div>
        <span>{{__('Ratio')}}<br/>{{ __(App\Helpers\LabelProviders::get($lunchType)) }}</span>
    </div>
    @endif
@endforeach
