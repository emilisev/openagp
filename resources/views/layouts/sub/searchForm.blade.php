<form method="POST" action="/daily">
    @csrf
    <div class="input-group">
        <div class="form-outline" data-mdb-input-init>
            <input type="text" name="notes" class="form-control" placeholder="{{ __("Chercher les notes") }}"/>
        </div>
        <button type="submit" class="btn btn-primary" data-mdb-ripple-init>
            <i class="bis bi-search"></i>
        </button>
    </div>
</form>
