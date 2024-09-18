@extends('usermanagement::layouts.master')

@section('content')
<div class="container mt-4">
<div class="card">
    <div class="card-header text-center font-weight-bold">
     my bee
    </div>
<div class="card-body">
<form action="{{ route('login.postLogin') }}" method="POST">
@csrf
        <h2>تسجيل الدخول</h2>
        <div class="form-group" >
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">كلمة المرور:</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
          <button type="submit">تسجيل الدخول</button>
        </div>
        <div class="error-message" id="error-message"></div>
        <div class="success-message" id="success-message"></div>
        </form>
</div>
</div>
</div>
    <script>
        async function login() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');
            
            errorMessage.textContent = '';
            successMessage.textContent = '';

            try {
                const response = await fetch({{ route('login.postLogin') }}, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.getElementByName('_token').getAttribute('value')
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                const result = await response.json();
                console.log(result);
                if (response.status === 200) {
                    successMessage.textContent = 'تم تسجيل الدخول بنجاح!';
                    // يمكنك تحويل المستخدم إلى صفحة أخرى إذا لزم الأمر:
                    // window.location.href = '/dashboard';
                } else {
                    errorMessage.textContent = 'فشل تسجيل الدخول: ' + result.message;
                }
            } catch (error) {
                errorMessage.textContent = 'حدث خطأ أثناء تسجيل الدخول. حاول مرة أخرى لاحقاً.';
            }
        }
    </script>
@endsection