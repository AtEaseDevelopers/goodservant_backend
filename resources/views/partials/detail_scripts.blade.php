{{-- Escape goes back to the list; hide the page loader. --}}
@once
@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                var back = document.getElementById('dv-back') || $('.card .card-header a')[0];
                if (back) back.click();
            }
        });
        $(document).ready(function () { HideLoad(); });
    </script>
@endpush
@endonce
