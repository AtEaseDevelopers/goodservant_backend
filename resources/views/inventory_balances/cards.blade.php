<div class="form-group">
    <input type="text" id="lorryCardFilter" class="form-control" placeholder="Search lorry...">
</div>

<div class="row" id="lorryCardGrid">
    @forelse($lorryGroups as $lorryno => $balances)
        <div class="col-md-4 col-lg-3 mb-4 lorry-balance-card" data-lorryno="{{ strtolower($lorryno) }}">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light">
                    <i class="fa fa-truck"></i>
                    <strong>{{ $lorryno }}</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('inventory_balances.product') }}</th>
                                <th class="text-right">{{ __('inventory_balances.quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balances->sortBy(fn($b) => $b->product->name ?? '') as $balance)
                            <tr>
                                <td>{{ $balance->product->name ?? '-' }}</td>
                                <td class="text-right">{{ $balance->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <p class="text-muted text-center py-4">No inventory balance records found.</p>
        </div>
    @endforelse
</div>
<div id="lorryCardEmpty" class="text-muted text-center py-4" style="display:none;">No lorries match your search.</div>

<script>
$(document).ready(function () {
    $('#lorryCardFilter').on('keyup', function () {
        var term = $(this).val().toLowerCase();
        var visibleCount = 0;
        $('.lorry-balance-card').each(function () {
            var match = $(this).data('lorryno').toString().includes(term);
            $(this).toggle(match);
            if (match) visibleCount++;
        });
        $('#lorryCardEmpty').toggle(visibleCount === 0);
    });
});
</script>
