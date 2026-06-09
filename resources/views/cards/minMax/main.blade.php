<div class="row justify-content-center mb-2">
    @foreach ($mins as $date => $min)
        @include('cards.minMax.square',
            ['type' => 'Min', 'date' => $date, 'value' => $min])
    @endforeach
    @foreach ($maxs as $date => $max)
        @include('cards.minMax.square',
             ['type' => 'Max', 'date' => $date, 'value' => $max])
    @endforeach
</div>
