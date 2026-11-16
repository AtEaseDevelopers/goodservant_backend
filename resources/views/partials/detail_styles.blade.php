{{-- Shared look for record view ("show") pages. Include once per page. --}}
@once
@push('styles')
<style>
    .dv-title { font-size: 1.4rem; font-weight: 600; margin: 0; }
    .dv-sub { color: #73818f; margin-top: .15rem; }
    .dv-fact { background: #f4f6f9; border-radius: .25rem; padding: .6rem .8rem; height: 100%; }
    .dv-fact-label { font-size: .75rem; color: #73818f; text-transform: uppercase; letter-spacing: .03em; }
    .dv-fact-value { font-size: 1rem; font-weight: 600; margin-top: .15rem; word-break: break-word; }
    .dv-section { border: 1px solid #e4e7ea; border-radius: .25rem; height: 100%; background: #fff; }
    .dv-section-title { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
                        color: #536c79; border-bottom: 1px solid #e4e7ea; padding: .55rem .9rem; background: #fafbfc; }
    .dv-section-note { text-transform: none; letter-spacing: 0; font-weight: 400; color: #73818f; }
    .dv-section dl { margin: 0; padding: .6rem .9rem .2rem; }
    .dv-section dt { font-weight: 400; color: #73818f; }
    .dv-section dd { margin-bottom: .55rem; word-break: break-word; }
    .dv-section .table { margin-bottom: 0; }
    .dv-section .table td, .dv-section .table th { vertical-align: middle; }
    .dv-pre { white-space: pre-wrap; word-break: break-word; background: #f4f6f9; border-radius: .25rem; padding: .5rem .7rem; margin: 0; font-size: .8rem; }
</style>
@endpush
@endonce
