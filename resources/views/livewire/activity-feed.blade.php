{{--
    Phase 2 (Blade Template Migration): the activity-feed presentation now lives in
    a Livewire-independent Blade partial driven purely by Alpine + browser
    CustomEvents. This Livewire component is kept as a thin wrapper so its existing
    mount point (<livewire:activity-feed> inside chat-interface) is unchanged and
    the migration stays fully reversible. The partial no longer touches $wire.
--}}
@include('partials.activity-feed', ['events' => $events, 'workflowId' => $workflowId])
