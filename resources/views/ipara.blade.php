<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('Payment') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        .card-container {
            perspective: 1000px;
        }

        .credit-card {
            transition: transform 0.8s;
            transform-style: preserve-3d;
            position: relative;
            width: 100%;
            height: 220px;
        }

        .credit-card.flipped {
            transform: rotateY(180deg);
        }

        .credit-card-front, .credit-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(22, 163, 74, 0.3);
        }

        .credit-card-front {
            background: linear-gradient(135deg, #16a34a, #15803d);
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .credit-card-back {
            background: linear-gradient(135deg, #15803d, #166534);
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
            transform: rotateY(180deg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Sadece CVV alanı görünen düzenlenmiş arka yüz */
        .card-cvv-container {
            background-color: #f0f0f0;
            width: 85%;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 0 auto;
        }

        #cardCvcDisplay {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            letter-spacing: 2px;
        }

        .cvv-info {
            margin-top: 15px;
            text-align: center;
            width: 100%;
            color: white;
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .card-chip {
            position: relative;
            width: 45px;
            height: 34px;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            border-radius: 5px;
            overflow: hidden;
        }

        .card-chip::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 35px;
            height: 24px;
            background: linear-gradient(135deg, #fbbf24, #fbbf24);
            border-radius: 3px;
        }

        .card-chip::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 25px;
            height: 15px;
            background: linear-gradient(135deg, #d97706, #d97706);
            border-radius: 2px;
        }

        /* Form input stillemesi */
        .form-input {
            transition: all 0.3s;
            border-width: 1px;
            border-color: #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
        }

        .form-input:focus {
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.1);
            border-color: #16a34a;
            outline: none;
        }
    </style>
</head>
<body>
<div class="bg-gradient-to-r from-green-600 to-green-500 text-white p-3 flex items-center justify-center shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 animate-pulse" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
    <p class="text-center font-medium">
        {{ __('Tüm ödemeleriniz 256 bit SSL ile korunmaktadır.') }}
    </p>
</div>

<div class="mx-auto max-w-7xl px-4 py-10">
    <div class="text-center mb-10">
        @php
            $logo = theme_option('logo_in_the_checkout_page') ?: theme_option('logo');
        @endphp

        @if ($logo)
            <div class="mx-auto mb-4">
                <img
                    src="{{ RvMedia::getImageUrl($logo) }}"
                    alt="{{ theme_option('site_title') }}"
                    class="h-14 md:h-16 mx-auto"
                />
            </div>
        @endif

        <h1 class="text-3xl md:text-4xl font-bold text-green-700 mt-6">
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-green-500">
                Güvenli Ödeme
            </span>
        </h1>

        <p class="mt-3 text-gray-600 max-w-xl mx-auto">
            Kredi kartı bilgilerinizi güvenle girin ve saniyeler içinde işleminizi tamamlayın.
        </p>
    </div>

    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-10">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <div class="p-8 flex flex-col justify-center items-center bg-gradient-to-br from-gray-50 to-green-50 lg:border-r lg:border-gray-200">
                    <div class="max-w-md w-full">
                        <div class="card-container mb-8">
                            <div class="credit-card" id="creditCard">
                                <div class="credit-card-front text-white">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-lg font-bold tracking-wider">BANKA KARTI</div>
                                            <div class="text-xs opacity-80 mt-1">Debit Card</div>
                                        </div>
                                        <div class="card-chip"></div>
                                    </div>

                                    <div class="absolute top-4 right-4 font-bold tracking-tighter text-xl text-white opacity-90">
                                        VISA
                                    </div>

                                    <div class="card-number mt-6 text-xl font-mono tracking-wider" id="cardNumberDisplay">
                                        •••• •••• •••• ••••
                                    </div>

                                    <div class="flex justify-between items-end mt-auto">
                                        <div>
                                            <div class="text-xs opacity-80">Kart Sahibi</div>
                                            <div class="font-medium tracking-wider" id="cardNameDisplay">AD SOYAD</div>
                                        </div>
                                        <div>
                                            <div class="text-xs opacity-80">Son Kullanma</div>
                                            <div class="font-mono" id="cardExpiryDisplay">MM/YY</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="credit-card-back">
                                    <div class="card-cvv-container">
                                        <div id="cardCvcDisplay" class="font-mono">***</div>
                                    </div>
                                    <div class="cvv-info">
                                        Kartınızın arkasında bulunan 3 haneli güvenlik kodu
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mt-8">
                            <div class="text-center">
                                <div class="bg-white p-3 rounded-full shadow-md inline-block mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-xs text-gray-600 font-medium">Güvenli Ödeme</div>
                            </div>

                            <div class="text-center">
                                <div class="bg-white p-3 rounded-full shadow-md inline-block mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-xs text-gray-600 font-medium">3D Secure</div>
                            </div>

                            <div class="text-center">
                                <div class="bg-white p-3 rounded-full shadow-md inline-block mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div class="text-xs text-gray-600 font-medium">SSL Koruması</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Kart Bilgileriniz</h2>

                    <form action="{{ route('payments.ipara.process') }}" method="post" id="payment-form" class="space-y-5">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $orderId }}">
                        <input type="hidden" name="amount" value="{{ $amount }}">
                        <input type="hidden" name="charge_id" value="{{ $charge_id }}">
                        <input type="hidden" name="success_url" value="{{ $successUrl }}">
                        <input type="hidden" name="fail_url" value="{{ $failUrl }}">
                        <input type="hidden" name="installment" value="1">

                        <div>
                            <label for="cardOwnerName" class="block text-sm font-medium text-gray-700 mb-1">Kart Sahibinin Adı Soyadı</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" class="form-input pl-10 py-3 w-full border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" id="cardOwnerName" name="cardOwnerName" placeholder="İsim Soyisim" required>
                            </div>
                        </div>

                        <div>
                            <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-1">Kart Numarası</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" class="form-input pl-10 py-3 w-full border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-green-500 sm:text-sm font-mono" id="cardTypeDisplay">VISA</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="cardExpireMonth" class="block text-sm font-medium text-gray-700 mb-1">Ay</label>
                                <select class="form-input py-3 w-full border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" id="cardExpireMonth" name="cardExpireMonth" required>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label for="cardExpireYear" class="block text-sm font-medium text-gray-700 mb-1">Yıl</label>
                                <select class="form-input py-3 w-full border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" id="cardExpireYear" name="cardExpireYear" required>
                                    @for ($i = date('y'); $i <= date('y') + 15; $i++)
                                        <option value="{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label for="cardCvc" class="block text-sm font-medium text-gray-700 mb-1">CVV/CVC</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="text" class="form-input py-3 w-full border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" id="cardCvc" name="cardCvc" placeholder="123" maxlength="4" required>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-medium text-green-800">Ödenecek Tutar</h3>
                                <p class="text-xs text-green-600 mt-1">Güvenli ödeme işlemi</p>
                            </div>
                            <div class="text-xl font-bold text-green-700">₺{{ number_format($amount / 100, 2, '.', ',') }}</div>
                        </div>

                       <div class="pt-2">
                            <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-medium py-3 px-4 rounded-md transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <span class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                    Güvenli Ödeme Yap
                                </span>
                            </button>
                        </div>


                        <div class="text-center text-xs text-gray-500 mt-4 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            Kart bilgileriniz şifrelenerek korunmaktadır.
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="text-center text-sm text-gray-500 mt-6">
            <div class="flex justify-center items-center space-x-6 mb-4">
                <img src="{{ asset('vendor/core/images/visa.svg') }}" alt="Visa" class="h-8" />
                <img src="{{ asset('vendor/core/images/mastercard.svg') }}" alt="Mastercard" class="h-8" />
                <img src="{{ asset('vendor/core/images/amex.svg') }}" alt="Amex" class="h-8" />
            </div>
            <p>© {{ date('Y') }} Kuyumcu Çarsı Tüm hakları saklıdır.</p>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/core/core/js-validation/js/js-validation.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const creditCard = document.getElementById('creditCard');
        const cardNumberInput = document.getElementById('cardNumber');
        const cardNameInput = document.getElementById('cardOwnerName');
        const cardMonthInput = document.getElementById('cardExpireMonth');
        const cardYearInput = document.getElementById('cardExpireYear');
        const cardCvcInput = document.getElementById('cardCvc');

        const cardNumberDisplay = document.getElementById('cardNumberDisplay');
        const cardNameDisplay = document.getElementById('cardNameDisplay');
        const cardExpiryDisplay = document.getElementById('cardExpiryDisplay');
        const cardCvcDisplay = document.getElementById('cardCvcDisplay');
        const cardTypeDisplay = document.getElementById('cardTypeDisplay');

        cardNumberInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');

            if (value.length > 16) {
                value = value.slice(0, 16);
            }

            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }

            this.value = formattedValue;

            if (value.length > 0) {
                cardNumberDisplay.textContent = formattedValue;
            } else {
                cardNumberDisplay.textContent = '•••• •••• •••• ••••';
            }

            updateCardType(value);
        });

        cardNameInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                cardNameDisplay.textContent = this.value.toUpperCase();
            } else {
                cardNameDisplay.textContent = 'AD SOYAD';
            }
        });

        function updateExpiry() {
            const month = cardMonthInput.value;
            const year = cardYearInput.value;

            if (month && year) {
                cardExpiryDisplay.textContent = `${month}/${year}`;
            } else {
                cardExpiryDisplay.textContent = 'MM/YY';
            }
        }

        cardMonthInput.addEventListener('change', updateExpiry);
        cardYearInput.addEventListener('change', updateExpiry);

        cardCvcInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');

            if (value.length > 4) {
                value = value.slice(0, 4);
            }

            this.value = value;

            if (value.length > 0) {
                cardCvcDisplay.textContent = value;
            } else {
                cardCvcDisplay.textContent = '***';
            }
        });

        cardCvcInput.addEventListener('focus', function() {
            creditCard.classList.add('flipped');
        });

        cardCvcInput.addEventListener('blur', function() {
            creditCard.classList.remove('flipped');
        });

        [cardNumberInput, cardNameInput, cardMonthInput, cardYearInput].forEach(input => {
            input.addEventListener('focus', function() {
                if (creditCard.classList.contains('flipped')) {
                    creditCard.classList.remove('flipped');
                }
            });
        });

        function updateCardType(number) {
            if (number.startsWith('4')) {
                cardTypeDisplay.textContent = 'VISA';
            } else if (/^5[1-5]/.test(number)) {
                cardTypeDisplay.textContent = 'MASTERCARD';
            } else if (/^3[47]/.test(number)) {
                cardTypeDisplay.textContent = 'AMEX';
            } else if (/^6(?:011|5)/.test(number)) {
                cardTypeDisplay.textContent = 'DISCOVER';
            } else {
                cardTypeDisplay.textContent = 'VISA';
            }
        }

        document.getElementById('payment-form').addEventListener('submit', function() {
            cardNumberInput.value = cardNumberInput.value.replace(/\s+/g, '');
        });

        updateExpiry();
    });
</script>
</body>
</html>
