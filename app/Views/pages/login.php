<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Monitoring System</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS + DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
      body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-base-200 font-inter min-h-screen flex items-center justify-center">
    
    <div class="card w-full max-w-sm shrink-0 bg-base-100 shadow-2xl">
        <form class="card-body" method="post" action="<?= site_url('login') ?>">
            <?= csrf_field() ?>

            <div class="flex flex-col items-center mb-4">
                 <div class="avatar mb-2">
                    <div class="w-16 h-16 rounded-lg bg-primary/10 flex items-center justify-center">
                        <img src="<?= base_url('assets/logo.png') ?>" alt="Logo" class="w-12 h-12 rounded">
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-center">Hidroganik Alfa</h1>
                <p class="text-sm text-base-content/60">Silakan login untuk melanjutkan</p>
            </div>
            
            <?php if (session()->getFlashdata('error')): ?>
                <div role="alert" class="alert alert-error text-sm p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?= session()->getFlashdata('error') ?></span>
                </div>
            <?php endif; ?>

            <div class="form-control">
                <label class="label">
                    <span class="label-text">Email</span>
                </label>
                <input type="email" name="email" placeholder="email@contoh.com" class="input input-bordered" required />
            </div>
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Password</span>
                </label>
                <input type="password" name="password" placeholder="********" class="input input-bordered" required />
            </div>
            <div class="form-control mt-6">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>

</body>
</html>
