<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Tambah Pengguna Baru</h1>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div role="alert" class="alert alert-error mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div role="alert" class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif; ?>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form action="<?= site_url('users/create') ?>" method="post">
                <?= csrf_field() ?>
                
                <div class="form-control">
                    <label class="label"><span class="label-text">Nama Lengkap</span></label>
                    <input type="text" name="displayName" placeholder="Contoh: John Doe" class="input input-bordered" value="<?= old('displayName') ?>" required>
                </div>

                <div class="form-control mt-4">
                    <label class="label"><span class="label-text">Email</span></label>
                    <input type="email" name="email" placeholder="contoh@email.com" class="input input-bordered" value="<?= old('email') ?>" required>
                </div>

                <div class="form-control mt-4">
                    <label class="label"><span class="label-text">Password</span></label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" class="input input-bordered" required>
                </div>

                <div class="form-control mt-4">
                    <label class="label"><span class="label-text">Role</span></label>
                    <select name="role" class="select select-bordered w-full">
                        <option value="user" <?= old('role') == 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= old('role') == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div class="form-control mt-8">
                    <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
