document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    fetch('/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${email}&password=${password}`
    })
    .then(response => response.json())
    .then(data => {
        const messageElement = document.getElementById('loginMessage');
        if (data.token) {
            messageElement.textContent = 'Login successful!';
            messageElement.style.color = 'green';
        } else {
            messageElement.textContent = data.message || 'Login failed';
            messageElement.style.color = 'red';
        }
    });
});

document.getElementById('registerForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);

    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageElement = document.getElementById('registerMessage');
        messageElement.textContent = data.message;
        if (data.message === 'Registration successful') {
            messageElement.style.color = 'green';
        } else {
            messageElement.style.color = 'red';
        }
    });
});

document.getElementById('loadCustomers').addEventListener('click', function() {
    fetch('/customers.php')
    .then(response => response.json())
    .then(data => {
        const customerList = document.getElementById('customerList');
        customerList.innerHTML = '';
        data.forEach(customer => {
            const li = document.createElement('li');
            li.textContent = `${customer.firstName} ${customer.lastName} - ${customer.email}`;
            customerList.appendChild(li);
        });
    });
});
