<script>
        const form = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');
        const loadingState = document.getElementById('loadingState');
        if (form) {
            form.addEventListener('submit', function() {
                payButton.disabled = true;
                loadingState.classList.remove('hidden');
            });
        }

        function openCardFallback(link) {
                var base   = link.getAttribute('href');
                var name   = document.querySelector('[name="customer_name"]')?.value || '';
                var email  = document.querySelector('[name="customer_email"]')?.value || '';
                var mobile = document.querySelector('[name="customer_mobile"]')?.value || '';
                var sep    = base.includes('?') ? '&' : '?';
                var url    = base + sep
                    + 'customer_name='    + encodeURIComponent(name)
                    + '&customer_email='  + encodeURIComponent(email)
                    + '&customer_mobile=' + encodeURIComponent(mobile);
                document.querySelectorAll('[name^="custom_fields["]').forEach(function(input) {
                    if (input.value) url += '&' + encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value);
                });
                window.open(url, '_blank', 'noopener');
                return false;
            }
            function toggleFallback() {
            var panel = document.getElementById('fallbackPanel');
            var chevron = document.getElementById('fallbackChevron');
            var toggle = document.getElementById('fallbackToggle');
            var isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden');
            if (isHidden) {
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
                toggle.style.color = '#3d01bd';
            } else {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
                toggle.style.color = '#9ca3af';
            }
        }
</script>
