<!DOCTYPE html>
<html>
<head>
    <title>Results</title>
</head>
<body>

<h2>Search Results</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>Room Type</th>
        <th>Available</th>
        <th>Price</th>
        <th>Original Price</th>
        <th>Discount</th>
    </tr>

    @foreach($results as $room)
        <tr>
            <td>{{ $room['room_type'] }}</td>
            <td>
                {{ $room['available'] ? 'Available' : 'Sold Out' }}
            </td>
            <td>{{ $room['price'] }}</td>
            <td>{{ $room['original_price'] }}</td>
            <td>{{ $room['discount_applied'] }}</td>
        </tr>
    @endforeach
</table>

</body>
</html>