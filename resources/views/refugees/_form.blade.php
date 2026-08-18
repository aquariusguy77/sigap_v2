@php
    $selectedStatus = old('status', $refugee->status ?? '');
    $selectedNationality = old('nationality', $refugee->nationality ?? '');
    $selectedLocation = old('location', $refugee->location ?? '');
@endphp

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="users" class="chip-icon" />Form Data Pengungsi</span>
                <h3>{{ $formMethod === 'POST' ? 'Isi data pengungsi baru' : 'Perbarui data pengungsi' }}</h3>
                <p class="section-intro">Lengkapi keempat langkah berikut, lalu simpan pada langkah terakhir.</p>
            </div>
            <span class="badge">{{ $formMethod === 'POST' ? 'Data baru' : 'Ubah data' }}</span>
        </div>

        @include('refugees._wizard_tabs')
        @include('refugees._step_identitas')
        @include('refugees._step_administrasi')
        @include('refugees._step_penempatan')
        @include('refugees._step_dokumen')

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;align-items:center;">
            <button type="button" class="btn btn-ghost wizard-prev">
                <x-icon name="x" class="chip-icon" /> Kembali
            </button>
            <button type="button" class="btn btn-primary wizard-next">
                Lanjut <x-icon name="check" class="chip-icon" />
            </button>
            <button class="btn btn-gold wizard-submit" type="submit" style="display:none;">
                <x-icon name="check" class="chip-icon" /> Simpan Data
            </button>
            <a class="btn btn-ghost" href="{{ route('refugees.index') }}">Batal</a>
        </div>
    </section>
</form>

<script>
    (() => {
        const tabs = Array.from(document.querySelectorAll('.wizard-tab'));
        const panels = Array.from(document.querySelectorAll('.wizard-panel'));
        const prevButton = document.querySelector('.wizard-prev');
        const nextButton = document.querySelector('.wizard-next');
        const submitButton = document.querySelector('.wizard-submit');
        let currentStep = 1;

        const renderStep = () => {
            tabs.forEach((tab, index) => {
                tab.classList.toggle('active', index + 1 === currentStep);
            });

            panels.forEach((panel) => {
                const active = Number(panel.dataset.stepPanel) === currentStep;
                panel.style.display = active ? 'block' : 'none';
                panel.classList.toggle('active', active);
            });

            if (prevButton) {
                prevButton.style.display = currentStep === 1 ? 'none' : 'inline-flex';
            }

            if (nextButton) {
                nextButton.style.display = currentStep === 4 ? 'none' : 'inline-flex';
            }

            if (submitButton) {
                submitButton.style.display = currentStep === 4 ? 'inline-flex' : 'none';
            }
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                currentStep = Number(tab.dataset.step);
                renderStep();
            });
        });

        prevButton?.addEventListener('click', () => {
            currentStep = Math.max(1, currentStep - 1);
            renderStep();
        });

        nextButton?.addEventListener('click', () => {
            currentStep = Math.min(4, currentStep + 1);
            renderStep();
        });

        renderStep();
    })();
</script>
