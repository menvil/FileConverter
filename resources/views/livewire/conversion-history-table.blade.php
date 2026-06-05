<div>
    @foreach ($jobs as $job)
        <div>{{ $job->sourceFile?->original_name }}</div>
    @endforeach
</div>
