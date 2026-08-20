-- Version 1 demo data. Admin login: admin / admin123
-- Hash from password_hash('admin123', PASSWORD_DEFAULT).

-- Five locations (FR2)
INSERT INTO branches (name, city, address, phone, email, opening_hours, latitude, longitude) VALUES
(
  'London Headquarters',
  'London',
  '14 Strand, Covent Garden, London WC2N 5HY',
  '020 7946 1975',
  'hq@bookandboard.co.uk',
  'Mon–Fri 09:00–18:00, Sat 10:00–16:00',
  51.510200,
  -0.123800
),
(
  'Manchester Branch',
  'Manchester',
  '22 King Street, Manchester M2 6AW',
  '0161 496 0123',
  'manchester@bookandboard.co.uk',
  'Mon–Sat 09:00–17:30',
  53.481000,
  -2.245000
),
(
  'Birmingham Branch',
  'Birmingham',
  '8 Corporation Street, Birmingham B2 4RN',
  '0121 496 0456',
  'birmingham@bookandboard.co.uk',
  'Mon–Sat 09:00–17:30',
  52.479800,
  -1.894500
),
(
  'Edinburgh Branch',
  'Edinburgh',
  '41 George Street, Edinburgh EH2 2HN',
  '0131 496 0789',
  'edinburgh@bookandboard.co.uk',
  'Mon–Sat 09:30–17:30',
  55.953300,
  -3.199800
),
(
  'Bristol Branch',
  'Bristol',
  '17 Park Street, Bristol BS1 5HR',
  '0117 496 0321',
  'bristol@bookandboard.co.uk',
  'Mon–Sat 09:00–17:00',
  51.454500,
  -2.603000
);

-- Current packages plus two expired ones (those should not show on public pages).
INSERT INTO offers
  (title, description, destination, price, image_url, is_bestseller, start_date, end_date)
VALUES
(
  'Paris City Break',
  'Three nights in a Left Bank boutique hotel with Eurostar from St Pancras, a Seine river cruise and a reserved Louvre timeslot. Breakfast included.',
  'Paris',
  429.00,
  '/assets/images/paris.png',
  1,
  '2026-06-01',
  '2026-12-31'
),
(
  'Rome & the Vatican',
  'Four-night Roman holiday with flights from Heathrow, a guided Colosseum tour and skip-the-line Vatican Museums entry. Centrally located 4-star hotel.',
  'Rome',
  689.00,
  '/assets/images/rome.png',
  1,
  '2026-05-15',
  '2027-01-31'
),
(
  'New York Shopping Escape',
  'Five nights in Midtown Manhattan, direct flight from Heathrow, MetroCard and a private SoHo shopping guide. Ideal for autumn city lights.',
  'New York',
  1299.00,
  '/assets/images/newyork.png',
  1,
  '2026-07-01',
  '2026-11-30'
),
(
  'Maldives Overwater Villa',
  'Seven nights half-board in an overwater villa with seaplane transfers from Malé. House reef snorkelling and a sunset cruise included.',
  'Maldives',
  2499.00,
  '/assets/images/maldives.png',
  1,
  '2026-04-01',
  '2027-03-31'
),
(
  'Greek Islands Island-Hop',
  'Nine nights across Santorini and Naxos with domestic ferry tickets, whitewashed cave-suite stay and a catamaran day-sail around the caldera.',
  'Santorini',
  1149.00,
  '/assets/images/santorini.png',
  0,
  '2026-05-01',
  '2026-10-31'
),
(
  'Dubai Sunshine Week',
  'Seven nights at a Jumeirah Beach 5-star, half-board, with a desert-safari evening and Burj Khalifa At the Top tickets. Flights from Heathrow.',
  'Dubai',
  1099.00,
  '/assets/images/dubai.png',
  0,
  '2026-06-01',
  '2027-02-28'
),
(
  'Barcelona Tapas Trail',
  'Four nights in the Gothic Quarter, flights from Gatwick, a guided tapas evening and Sagrada Família timed entry.',
  'Barcelona',
  559.00,
  '/assets/images/barcelona.png',
  0,
  '2026-03-01',
  '2026-12-15'
),
(
  'Scottish Highlands Rail Journey',
  'Five-night escorted tour from Edinburgh to the Isle of Skye by rail and coach, including a steam-train excursion and a whisky distillery visit.',
  'Scottish Highlands',
  799.00,
  '/assets/images/highlands.png',
  0,
  '2026-04-15',
  '2026-10-31'
),
(
  'Tokyo Cherry Season',
  'Eight nights in Shinjuku with flights from Heathrow, a Hakone day trip, a sushi-making class and a JR metro pass.',
  'Tokyo',
  1899.00,
  '/assets/images/tokyo.png',
  0,
  '2026-09-01',
  '2027-04-30'
),
(
  'Amsterdam Canal Weekend',
  'Two nights beside the Herengracht, Eurostar via Brussels, a canal-cruise ticket and Rijksmuseum entry. Compact and easy from London.',
  'Amsterdam',
  349.00,
  '/assets/images/amsterdam.png',
  0,
  '2026-02-01',
  '2026-12-31'
),
-- Expired offers: public pages must hide these (end_date < today).
(
  'Lisbon Spring Escape (ended)',
  'This package has expired and must not appear on public offer listings.',
  'Lisbon',
  399.00,
  '/assets/images/amsterdam.png',
  0,
  '2026-01-01',
  '2026-06-30'
),
(
  'Iceland Northern Lights (ended)',
  'Winter 2025/26 departure window — expired, hidden from the public site.',
  'Reykjavik',
  899.00,
  '/assets/images/highlands.png',
  0,
  '2025-11-01',
  '2026-03-31'
);

-- Staff account for FR5
INSERT INTO admins (username, password_hash) VALUES
(
  'admin',
  '$2y$12$98AHMvXDZMROo4I19n1o.u05zumJ6mELCh3wEF4Bmj6pj.sRhMZsm'
);
