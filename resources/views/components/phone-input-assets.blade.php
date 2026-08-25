
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/css/intlTelInput.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js"></script>
<script>
    (function () {
        const phoneInputFields = document.querySelectorAll('.phone-input-control');

        phoneInputFields.forEach((phoneInput, index) => {
            const codeInput = document.querySelector(phoneInput.dataset.codeInput || `#code${index}`);
            const countryInput = document.querySelector(phoneInput.dataset.countryInput || `#country${index}`);

            const iti = window.intlTelInput(phoneInput, {

                separateDialCode: true,
                geoIpLookup: function(callback) {
                    getIp(function(countryCode) {
                        callback(countryCode.toLowerCase());
                    });
                },
                utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js',
            });

            phoneInput.addEventListener('countrychange', function() {
                if (codeInput) {
                    codeInput.value = '+' + iti.getSelectedCountryData().dialCode;
                }
                if (countryInput) {
                    countryInput.value = iti.getSelectedCountryData().name;
                }
                updateSelectedCountryName(phoneInput, iti);
            });

            const initialPhone = phoneInput.value;
            const initialCode = codeInput ? codeInput.value : '';
            const initialCountry = countryInput ? countryInput.value : '';

            if (initialPhone) {
                iti.setNumber(initialPhone);
                if (codeInput && initialCode) {
                    codeInput.value = initialCode;
                }
                if (countryInput && initialCountry) {
                    countryInput.value = initialCountry;
                }
            } else {
                if (codeInput) {
                    codeInput.value = '+' + iti.getSelectedCountryData().dialCode;
                }
                if (countryInput) {
                    countryInput.value = iti.getSelectedCountryData().name;
                }
            }

            const form = phoneInput.closest('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!iti.isValidNumber()) {
                        event.preventDefault();
                        phoneInput.classList.add('is-invalid');
                        let errorSpan = phoneInput.parentElement.querySelector('.text-danger');
                        if (!errorSpan) {
                            errorSpan = document.createElement('span');
                            errorSpan.className = 'text-danger';
                            errorSpan.textContent = 'Please enter a valid phone number.';
                            phoneInput.parentElement.appendChild(errorSpan);
                        }
                    } else {
                        phoneInput.classList.remove('is-invalid');
                        const errorSpan = phoneInput.parentElement.querySelector('.text-danger');
                        if (errorSpan) errorSpan.remove();
                        phoneInput.value = iti.getNumber();
                    }
                });
            }
        });

        function updateSelectedCountryName(phoneInput, iti) {
            const selectedFlag = phoneInput.parentNode.querySelector('.iti__selected-flag');
            let nameSpan = selectedFlag.querySelector('.selected-country-name');

            const countryData = iti.getSelectedCountryData();

            if (!nameSpan) {
                nameSpan = document.createElement('span');
                nameSpan.className = 'selected-country-name';
                nameSpan.style.marginLeft = '6px';
                nameSpan.style.fontWeight = '500';
                selectedFlag.appendChild(nameSpan);
            }

            nameSpan.textContent = countryData.name;
        }

        function getIp(callback) {
            fetch('https://ipinfo.io/json?token=a876c4d470b426', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then((resp) => resp.json())
                .catch(() => {
                    return {
                        country: 'ke'
                    };
                })
                .then((resp) => callback(resp.country));
        }
    })();
</script>
@endpush
