footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                © {{ date('Y') }} {{ config('app.name', 'Laravel') }}
            </div>
            <div class="col-md-6 text-end">
                <a href="/privacy" class="text-white">Политика конфиденциальности</a>
            </div>
        </div>
    </div>
</footer>