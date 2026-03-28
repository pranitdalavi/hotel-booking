<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking Search</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; max-width: 900px; }
        input, select, button { padding: 8px; margin: 6px 0; width: 100%; max-width: 320px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f5f5f5; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; color: #fff; font-size: 0.9rem; }
        .available { background: #28a745; }
        .sold-out { background: #dc3545; }
        .error { color: #dc3545; }
        .success { color: #28a745; }
        .section { margin-top: 20px; }
        .inline { display: inline-block; margin-right: 8px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Hotel Booking Search</h1>
    <p>Search room availability, pricing, discounts and meal plans for the next 30 days.</p>

    <form id="searchForm">
        <div>
            <label for="check_in">Check-in</label><br>
            <input id="check_in" name="check_in" type="date" required>
        </div>

        <div>
            <label for="check_out">Check-out</label><br>
            <input id="check_out" name="check_out" type="date" required>
        </div>

        <div>
            <label for="guests">Adults</label><br>
            <input id="guests" name="guests" type="number" min="1" max="3" value="1" required>
        </div>

        <div>
            <label for="meal_plan">Meal Plan</label><br>
            <select id="meal_plan" name="meal_plan">
                <option value="room_only">Room Only</option>
                <option value="breakfast">Breakfast Included</option>
            </select>
        </div>

        <div>
            <button type="submit">Search</button>
        </div>
    </form>

    <div id="messages"></div>
    <div id="results"></div>
    <div id="bookingSection"></div>
    <div id="paymentSection"></div>
</div>

<script>
const form = document.getElementById('searchForm');
const messages = document.getElementById('messages');
const resultsContainer = document.getElementById('results');
const bookingSection = document.getElementById('bookingSection');
const paymentSection = document.getElementById('paymentSection');

let latestSearch = null;
let currentBooking = null;

function getSearchPayload() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const guests = document.getElementById('guests').value;
    const mealPlan = document.getElementById('meal_plan').value;

    return { check_in: checkIn, check_out: checkOut, guests: guests, meal_plan: mealPlan };
}

function renderResults(data) {
    if (!data.length) {
        resultsContainer.innerHTML = '<p>No room types match your search. Please adjust dates or guest count.</p>';
        return;
    }

    let html = '<table><thead><tr>' +
        '<th>Room Type</th>' +
        '<th>Status</th>' +
        '<th>Available Rooms</th>' +
        '<th>Stay Nights</th>' +
        '<th>Meal Plan</th>' +
        '<th>Original Price</th>' +
        '<th>Discount</th>' +
        '<th>Final Price</th>' +
        '<th>Action</th>' +
        '</tr></thead><tbody>';

    data.forEach(item => {
        const status = item.available
            ? '<span class="badge available">Available</span>'
            : '<span class="badge sold-out">Sold Out</span>';

        html += '<tr>' +
            `<td>${item.room_type}</td>` +
            `<td>${status}</td>` +
            `<td>${item.available_rooms ?? 0}</td>` +
            `<td>${item.stay_nights}</td>` +
            `<td>${item.meal_plan.replace('_', ' ')}</td>` +
            `<td>${item.original_price !== null ? '₹' + item.original_price.toFixed(2) : 'N/A'}</td>` +
            `<td>₹${item.discount_applied.toFixed(2)}</td>` +
            `<td>${item.final_price !== null ? '₹' + item.final_price.toFixed(2) : 'N/A'}</td>` +
            `<td>${item.available ? `<button type="button" class="book-btn" data-room-id="${item.room_type_id}" data-room-name="${item.room_type}">Book</button>` : '-'}</td>` +
            '</tr>';
    });

    html += '</tbody></table>';
    resultsContainer.innerHTML = html;
}

function renderMessage(message, isError = false) {
    messages.innerHTML = `<p class="${isError ? 'error' : 'success'}">${message}</p>`;
}

function showBookingDetails(data, roomName) {
    bookingSection.innerHTML = `
        <div class="section">
            <h2>Booking created</h2>
            <p><strong>Room:</strong> ${roomName}</p>
            <p><strong>Booking ID:</strong> ${data.booking_id}</p>
            <p><strong>Amount Due:</strong> ₹${data.amount_due.toFixed(2)}</p>
        </div>
    `;
}

function showPaymentForm(bookingId) {
    paymentSection.innerHTML = `
        <div class="section">
            <h2>Payment</h2>
            <p>Select a payment method and complete the booking.</p>
            <div>
                <label class="inline"><input type="radio" name="payment_method" value="card" checked> Card</label>
                <label class="inline"><input type="radio" name="payment_method" value="upi"> UPI</label>
            </div>
            <div>
                <button id="payButton" type="button" data-booking-id="${bookingId}">Pay Now</button>
            </div>
        </div>
    `;
}

async function bookRoom(roomTypeId, roomName) {
    const payload = {
        ...getSearchPayload(),
        room_type_id: roomTypeId,
    };

    renderMessage('Creating booking...');
    bookingSection.innerHTML = '';
    paymentSection.innerHTML = '';

    try {
        const response = await fetch('/api/book', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            const error = result.errors ? Object.values(result.errors).flat().join('<br>') : result.message || 'Booking failed.';
            renderMessage(error, true);
            return;
        }

        currentBooking = result.data;
        renderMessage('Booking created successfully. Proceed to payment.');
        showBookingDetails(result.data, roomName);
        showPaymentForm(result.data.booking_id);
    } catch (error) {
        renderMessage('Unable to create booking. Please try again.', true);
    }
}

async function payBooking(bookingId) {
    const paymentMethodElement = document.querySelector('input[name="payment_method"]:checked');
    const paymentMethod = paymentMethodElement ? paymentMethodElement.value : 'card';

    renderMessage('Processing payment...');

    try {
        const response = await fetch('/api/pay', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ booking_id: bookingId, payment_method: paymentMethod }),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            const error = result.errors ? Object.values(result.errors).flat().join('<br>') : result.message || 'Payment failed.';
            renderMessage(error, true);
            return;
        }

        renderMessage('Payment completed successfully. Booking confirmed.');
        paymentSection.innerHTML = `
            <div class="section">
                <h2>Payment successful</h2>
                <p><strong>Payment ID:</strong> ${result.data.payment_id}</p>
                <p><strong>Transaction:</strong> ${result.data.transaction_id}</p>
            </div>
        `;
    } catch (error) {
        renderMessage('Unable to process payment. Please try again.', true);
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    messages.innerHTML = '';
    resultsContainer.innerHTML = '';
    bookingSection.innerHTML = '';
    paymentSection.innerHTML = '';

    const formData = new FormData(form);
    const params = new URLSearchParams();

    const payload = {};
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
            payload[key] = value;
        }
    }

    latestSearch = payload;

    try {
        const response = await fetch('/api/search?' + params.toString(), {
            headers: {
                'Accept': 'application/json'
            }
        });
        const payloadResponse = await response.json();

        if (!response.ok || !payloadResponse.success) {
            const error = payloadResponse.errors ? Object.values(payloadResponse.errors).flat().join('<br>') : payloadResponse.message || 'Search failed.';
            renderMessage(error, true);
            return;
        }

        renderResults(payloadResponse.data);
        renderMessage('Search completed. Select an available room to book.');
    } catch (error) {
        renderMessage('Unable to complete search. Please try again.', true);
    }
});

resultsContainer.addEventListener('click', (event) => {
    if (event.target.matches('.book-btn')) {
        const roomTypeId = event.target.dataset.roomId;
        const roomName = event.target.dataset.roomName;
        bookRoom(roomTypeId, roomName);
    }
});

paymentSection.addEventListener('click', (event) => {
    if (event.target.id === 'payButton') {
        const bookingId = event.target.dataset.bookingId;
        payBooking(bookingId);
    }
});
</script>
</body>
</html>
