-- Demo data for Version 2.
-- Staff: admin / admin123
-- Customers: info@oscarmini.com / demo123   jane.doe@example.com / traveller1
-- Hashes from password_hash(..., PASSWORD_DEFAULT).

-- Five locations (FR2)
INSERT INTO branches (name, city, address, phone, email, opening_hours, latitude, longitude) VALUES
('London Headquarters', 'London', '14 Strand, Covent Garden, London WC2N 5HY', '020 7946 1975', 'hq@bookandboard.co.uk', 'Mon–Fri 09:00–18:00, Sat 10:00–16:00', 51.510200, -0.123800),
('Manchester Branch', 'Manchester', '22 King Street, Manchester M2 6AW', '0161 496 0123', 'manchester@bookandboard.co.uk', 'Mon–Sat 09:00–17:30', 53.481000, -2.245000),
('Birmingham Branch', 'Birmingham', '8 Corporation Street, Birmingham B2 4RN', '0121 496 0456', 'birmingham@bookandboard.co.uk', 'Mon–Sat 09:00–17:30', 52.479800, -1.894500),
('Edinburgh Branch', 'Edinburgh', '41 George Street, Edinburgh EH2 2HN', '0131 496 0789', 'edinburgh@bookandboard.co.uk', 'Mon–Sat 09:30–17:30', 55.953300, -3.199800),
('Bristol Branch', 'Bristol', '17 Park Street, Bristol BS1 5HR', '0117 496 0321', 'bristol@bookandboard.co.uk', 'Mon–Sat 09:00–17:00', 51.454500, -2.603000);

-- Ten current offers + two expired (the public pages hide those). Four bestsellers.
INSERT INTO offers
  (title, description, destination, price, image_url, is_bestseller, start_date, end_date)
VALUES
('Paris City Break', 'Three nights in a Left Bank boutique hotel with Eurostar from St Pancras, a Seine river cruise and a reserved Louvre timeslot. Breakfast included.', 'Paris', 429.00, '/assets/images/paris.png', 1, '2026-06-01', '2026-12-31'),
('Rome & the Vatican', 'Four-night Roman holiday with flights from Heathrow, a guided Colosseum tour and skip-the-line Vatican Museums entry. Centrally located 4-star hotel.', 'Rome', 689.00, '/assets/images/rome.png', 1, '2026-05-15', '2027-01-31'),
('New York Shopping Escape', 'Five nights in Midtown Manhattan, direct flight from Heathrow, MetroCard and a private SoHo shopping guide. Ideal for autumn city lights.', 'New York', 1299.00, '/assets/images/newyork.png', 1, '2026-07-01', '2026-11-30'),
('Maldives Overwater Villa', 'Seven nights half-board in an overwater villa with seaplane transfers from Malé. House reef snorkelling and a sunset cruise included.', 'Maldives', 2499.00, '/assets/images/maldives.png', 1, '2026-04-01', '2027-03-31'),
('Greek Islands Island-Hop', 'Nine nights across Santorini and Naxos with domestic ferry tickets, whitewashed cave-suite stay and a catamaran day-sail around the caldera.', 'Santorini', 1149.00, '/assets/images/santorini.png', 0, '2026-05-01', '2026-10-31'),
('Dubai Sunshine Week', 'Seven nights at a Jumeirah Beach 5-star, half-board, with a desert-safari evening and Burj Khalifa At the Top tickets. Flights from Heathrow.', 'Dubai', 1099.00, '/assets/images/dubai.png', 0, '2026-06-01', '2027-02-28'),
('Barcelona Tapas Trail', 'Four nights in the Gothic Quarter, flights from Gatwick, a guided tapas evening and Sagrada Família timed entry.', 'Barcelona', 559.00, '/assets/images/barcelona.png', 0, '2026-03-01', '2026-12-15'),
('Scottish Highlands Rail Journey', 'Five-night escorted tour from Edinburgh to the Isle of Skye by rail and coach, including a steam-train excursion and a whisky distillery visit.', 'Scottish Highlands', 799.00, '/assets/images/highlands.png', 0, '2026-04-15', '2026-10-31'),
('Tokyo Cherry Season', 'Eight nights in Shinjuku with flights from Heathrow, a Hakone day trip, a sushi-making class and a JR metro pass.', 'Tokyo', 1899.00, '/assets/images/tokyo.png', 0, '2026-09-01', '2027-04-30'),
('Amsterdam Canal Weekend', 'Two nights beside the Herengracht, Eurostar via Brussels, a canal-cruise ticket and Rijksmuseum entry. Compact and easy from London.', 'Amsterdam', 349.00, '/assets/images/amsterdam.png', 0, '2026-02-01', '2026-12-31'),
('Lisbon Spring Escape (ended)', 'Expired package — must not appear on public listings.', 'Lisbon', 399.00, '/assets/images/amsterdam.png', 0, '2026-01-01', '2026-06-30'),
('Iceland Northern Lights (ended)', 'Expired package — must not appear on public listings.', 'Reykjavik', 899.00, '/assets/images/highlands.png', 0, '2025-11-01', '2026-03-31');

INSERT INTO admins (username, password_hash) VALUES
('admin', '$2y$12$98AHMvXDZMROo4I19n1o.u05zumJ6mELCh3wEF4Bmj6pj.sRhMZsm');

-- Demo customers for login / dashboard
INSERT INTO users (name, email, password_hash, phone, address) VALUES
(
  'Oscar Frank',
  'info@oscarmini.com',
  '$2y$12$ZrpsHkQs1W4xHHv/GIDmjOoqZZV7kF5j0teILSvAPoLDhnOTro9oW',
  '07700 900123',
  '18 Maple Avenue, Richmond, London TW9 1AB'
),
(
  'Jane Doe',
  'jane.doe@example.com',
  '$2y$12$uOhGiuC5X1t13EDxu/v0Xeo0v256i9UGjSBkzvXGcERQA9eaInmJG',
  '07700 900456',
  '4 Canal Street, Manchester M1 3HE'
);

-- Sample bookings so the dashboard isn't empty on first login (FR9).
INSERT INTO bookings (user_id, package_name, destination, travel_date, price, status) VALUES
(1, 'Paris City Break', 'Paris', '2025-09-12', 429.00, 'completed'),
(1, 'Amsterdam Canal Weekend', 'Amsterdam', '2026-03-07', 349.00, 'confirmed'),
(1, 'Barcelona Tapas Trail', 'Barcelona', '2026-10-18', 559.00, 'confirmed'),
(2, 'Scottish Highlands Rail Journey', 'Scottish Highlands', '2025-11-02', 799.00, 'completed'),
(2, 'Dubai Sunshine Week', 'Dubai', '2026-09-04', 1099.00, 'confirmed');

-- Flights for search-flights.php. London-Paris on 2026-08-20 is the demo search.
INSERT INTO flights
  (airline, origin, destination, depart_time, arrive_time, duration_minutes, stops, price)
VALUES
('British Airways', 'London', 'Paris',     '2026-08-20 07:15:00', '2026-08-20 09:25:00', 130, 0,  89.00),
('easyJet',         'London', 'Paris',     '2026-08-20 12:40:00', '2026-08-20 14:55:00', 135, 0,  64.00),
('Air France',      'London', 'Paris',     '2026-08-21 18:10:00', '2026-08-21 22:40:00', 270, 1,  79.00),
('British Airways', 'London', 'Rome',      '2026-08-22 06:50:00', '2026-08-22 10:20:00', 210, 0, 129.00),
('Ryanair',         'London', 'Rome',      '2026-08-22 14:05:00', '2026-08-22 20:15:00', 370, 1,  74.00),
('ITA Airways',     'London', 'Rome',      '2026-09-05 09:00:00', '2026-09-05 12:25:00', 205, 0, 149.00),
('Virgin Atlantic', 'London', 'New York',  '2026-08-25 10:30:00', '2026-08-25 13:15:00', 465, 0, 489.00),
('British Airways', 'London', 'New York',  '2026-08-25 16:45:00', '2026-08-26 01:10:00', 625, 1, 399.00),
('Norse Atlantic',  'London', 'New York',  '2026-09-10 13:20:00', '2026-09-10 16:00:00', 460, 0, 329.00),
('Emirates',        'London', 'Dubai',     '2026-08-28 21:00:00', '2026-08-29 06:50:00', 410, 0, 459.00),
('British Airways', 'London', 'Dubai',     '2026-08-28 14:10:00', '2026-08-29 03:40:00', 630, 1, 379.00),
('Vueling',         'London', 'Barcelona', '2026-08-19 08:25:00', '2026-08-19 11:30:00', 125, 0,  69.00),
('easyJet',         'London', 'Barcelona', '2026-08-19 19:50:00', '2026-08-19 23:00:00', 130, 0,  55.00),
('KLM',             'London', 'Amsterdam', '2026-08-18 07:05:00', '2026-08-18 09:15:00',  70, 0,  78.00),
('Japan Airlines',  'London', 'Tokyo',     '2026-09-12 11:00:00', '2026-09-13 08:20:00', 740, 0, 789.00),
('ANA',             'London', 'Tokyo',     '2026-09-12 13:40:00', '2026-09-13 16:10:00', 990, 1, 649.00),
('KLM',             'Manchester', 'Amsterdam', '2026-08-20 06:40:00', '2026-08-20 09:05:00',  85, 0,  92.00),
('Ryanair',         'Manchester', 'Barcelona', '2026-08-21 13:15:00', '2026-08-21 16:35:00', 140, 0,  48.00),
('easyJet',         'Edinburgh', 'Paris',  '2026-08-23 10:20:00', '2026-08-23 13:25:00', 125, 0,  71.00),
('Jet2',            'Edinburgh', 'Barcelona', '2026-08-24 16:00:00', '2026-08-24 22:10:00', 310, 1,  86.00),
('Ryanair',         'Birmingham', 'Malaga', '2026-08-26 06:10:00', '2026-08-26 10:00:00', 170, 0,  59.00),
('TUI Airways',     'Birmingham', 'Malaga', '2026-08-26 15:45:00', '2026-08-26 22:30:00', 345, 1,  99.00);

-- Hotels for search-hotels.php. Barcelona / Paris both return more than one row.
INSERT INTO hotels (name, city, star_rating, price_per_night, amenities, image_url) VALUES
('Hôtel du Quai', 'Paris', 3, 142.00, 'Wi-Fi, Breakfast, River view', '/assets/images/paris.png'),
('Left Bank Residences', 'Paris', 4, 218.00, 'Wi-Fi, Spa, Restaurant', '/assets/images/paris.png'),
('Palatine Inn', 'Rome', 4, 189.00, 'Wi-Fi, Breakfast, Terrace', '/assets/images/rome.png'),
('Trastevere House', 'Rome', 3, 121.00, 'Wi-Fi, Air conditioning', '/assets/images/rome.png'),
('Fifth Avenue Suites', 'New York', 5, 412.00, 'Wi-Fi, Gym, Concierge, Restaurant', '/assets/images/newyork.png'),
('Jumeirah Garden Hotel', 'Dubai', 5, 276.00, 'Wi-Fi, Pool, Beach, Spa', '/assets/images/dubai.png'),
('Gothic Quarter Rooms', 'Barcelona', 3, 98.00, 'Wi-Fi, Breakfast', '/assets/images/barcelona.png'),
('Eixample Grand', 'Barcelona', 4, 164.00, 'Wi-Fi, Pool, Restaurant', '/assets/images/barcelona.png'),
('New Town Townhouse', 'Edinburgh', 4, 155.00, 'Wi-Fi, Breakfast, Garden', '/assets/images/highlands.png'),
('Shinjuku Park Hotel', 'Tokyo', 4, 198.00, 'Wi-Fi, Onsen, Restaurant', '/assets/images/tokyo.png'),
('Herengracht House', 'Amsterdam', 3, 134.00, 'Wi-Fi, Canal view, Bicycle hire', '/assets/images/amsterdam.png'),
('Caldera Cave Suites', 'Santorini', 5, 310.00, 'Wi-Fi, Infinity pool, Breakfast', '/assets/images/santorini.png');
