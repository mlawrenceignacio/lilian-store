# Lilian Online Store

A responsive PHP and MySQL e-commerce system for **Lilian Sari-Sari Store**, built for web deployment using XAMPP locally and free PHP/MySQL hosting for production demo purposes.

## Overview

Lilian Online Store is a role-based online store system designed for both **customers** and **administrators** in one website. It allows customers to browse products, add items to cart, place orders, apply vouchers, track delivery progress, and submit ratings after completed orders. On the admin side, it provides dashboard statistics, order management, notifications, activity logs, product management, and admin profile/account viewing.

## Features

### Customer Features
- Responsive homepage, shop, about, profile, vouchers, cart, checkout, and orders pages
- Product browsing with search and category filters
- Cart and checkout flow
- Payment methods:
  - Cash on Delivery (COD)
  - GCash
  - GoTyme
- Manual payment proof upload for GCash and GoTyme
- Voucher claiming and voucher application during checkout
- Order tracking by status:
  - To Pay
  - To Ship
  - To Receive
  - To Rate
- Order cancellation for eligible statuses
- Product rating and review after delivered orders

### Admin Features
- Dashboard with order and customer statistics
- Bar graph overview
- Notifications for:
  - New orders
  - Cancelled orders
  - Orders marked as received
- Orders management with status updates
- Product management:
  - Add product
  - Edit product
  - Remove product
  - Rating remains user-generated and read-only to admin
- Activity log with timestamped admin actions
- Admin profile with editable username
- View-only admin accounts list

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP
- **Deployment Target:** Free PHP/MySQL hosting

