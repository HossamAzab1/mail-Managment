    </div>
    <footer class="bg-blue-600 dark:bg-blue-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>نظام إدارة البريد &copy; <?= date('Y') ?></p>
            <p>مع تحياتي اخوك حسام عزب</p>
        </div>
    </footer>
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });

        // Set initial theme
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</body>
</html>