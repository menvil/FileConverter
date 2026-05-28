<x-layouts.app title="Dashboard — ConvertAI">
    <section class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Convert any file</h1>
        <p class="text-[var(--ca-muted)]">Upload, choose a target format, fine-tune the result, and convert in one click.</p>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <x-card variant="elevated">
                <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="File" />

                <div class="mt-6">
                    <x-conversion.dropzone variant="simple" />
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2 text-sm text-[var(--ca-muted)]">
                    <span>Try:</span>
                    <x-file-icon format="pdf" size="sm" />
                    <x-file-icon format="jpg" size="sm" />
                    <x-file-icon format="png" size="sm" />
                    <x-file-icon format="webp" size="sm" />
                </div>
            </x-card>
        </div>

        <aside class="flex flex-col gap-6">
            <x-card variant="gradient">
                <h2 class="text-base font-semibold">Your plan</h2>
                <p class="mt-1 text-sm opacity-90">PRO · 50 credits remaining</p>
                <p class="mt-3 text-xs opacity-80">Upgrade to Max for batch conversions and larger files.</p>
            </x-card>

            <x-card variant="elevated">
                <h2 class="text-base font-semibold text-[var(--ca-text)]">Popular conversions</h2>
                <ul class="mt-3 flex flex-col gap-2 text-sm">
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>PNG → JPG</span>
                        <span class="text-xs text-[var(--ca-muted)]">Image</span>
                    </li>
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>JPG → WEBP</span>
                        <span class="text-xs text-[var(--ca-muted)]">Image</span>
                    </li>
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>Image → PDF</span>
                        <span class="text-xs text-[var(--ca-muted)]">Document</span>
                    </li>
                </ul>
            </x-card>
        </aside>
    </div>

    <div class="mt-10">
        <x-table.recent-conversions
            :rows="[
                ['name' => 'Marketing Report.pdf', 'from' => 'pdf', 'to' => 'docx', 'size' => '24.8 MB', 'date' => 'May 12, 10:24 AM', 'status' => 'completed'],
                ['name' => 'Project Proposal.docx', 'from' => 'jpg', 'to' => 'pdf', 'size' => '1.8 MB', 'date' => 'May 12, 09:58 AM', 'status' => 'completed', 'starred' => true],
                ['name' => 'Sales Data.xlsx', 'from' => 'png', 'to' => 'webp', 'size' => '612 KB', 'date' => 'May 12, 09:15 AM', 'status' => 'completed'],
                ['name' => 'Product Demo.mp4', 'from' => 'png', 'to' => 'jpg', 'size' => '84 MB', 'date' => 'May 11, 04:42 PM', 'status' => 'completed'],
                ['name' => 'Screenshot 2024.jpg', 'from' => 'jpg', 'to' => 'png', 'size' => '3.2 MB', 'date' => 'May 11, 03:33 PM', 'status' => 'completed'],
                ['name' => 'Contract.doc', 'from' => 'pdf', 'to' => 'pdf', 'size' => '412 KB', 'date' => 'May 11, 11:07 AM', 'status' => 'processing'],
                ['name' => 'Client_Presentation.pptx', 'from' => 'pdf', 'to' => 'pdf', 'size' => '5.1 MB', 'date' => 'May 10, 06:20 PM', 'status' => 'completed', 'starred' => true],
                ['name' => 'Annual_Budget.xlsx', 'from' => 'pdf', 'to' => 'pdf', 'size' => '2.4 MB', 'date' => 'May 10, 02:05 PM', 'status' => 'completed'],
            ]"
        />
    </div>

    <div class="mt-10">
        <x-footer-help-cards />
    </div>
</x-layouts.app>
