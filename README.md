# 🎨 Artsly – C2C Art Marketplace

Artsly is a web-based Customer-to-Customer (C2C) e-commerce platform that connects artists and buyers. It allows artists to showcase and sell their artwork, while users can explore, search, and purchase unique creations in a secure and user-friendly environment.

---

## 🚀 Features

- 🔐 User Registration & Login  
- 🧾 Artist KYC Verification  
- 🖼️ Artwork Upload & Management  
- 🔍 Search, Filter & Sort (Dynamic Query)  
- 🛒 Cart Management  
- 📦 Order Placement & Tracking  
- 💳 eSewa Payment (Receipt Upload System)  
- 🛠️ Admin Panel (User & KYC Management)

---

## 🛠️ Tech Stack

- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP  
- **Database:** MySQL  

---

## ⚙️ Installation & Setup

1. Clone the repository:
```bash
git clone https://github.com/sauch0/Artsly.git
```

2. Move project to XAMPP `htdocs` folder:
```
C:/xampp/htdocs/artsly
```

3. Start Apache and MySQL from XAMPP.

4. Import the database:
- Open **phpMyAdmin**
- Create a database (e.g., `artsly`)
- Import the provided `.sql` file

5. Update database connection:
```php
$conn = mysqli_connect("localhost", "root", "", "artsly");
```

6. Run the project:
```
http://localhost/artsly
```

---

## 🔑 User Roles

### 👤 Buyer
- Browse and search artworks  
- Add to cart and place orders  
- Upload payment receipt  
- Track orders  

### 🎨 Artist
- Submit KYC for verification  
- Upload and manage artworks  
- View and manage orders  
- Verify payments  

### 🛡️ Admin
- Verify artist KYC  
- Manage users and artworks  
- Monitor orders and transactions  

---

## 💡 Key Concepts Used

- Session Management (Authentication)  
- Dynamic SQL Query Building  
- File Upload Handling  
- Role-Based Access Control  
- C2C E-Commerce Workflow  

---

## 🔮 Future Improvements

- Rating & Review System  
- Chat between buyer and artist  
- Recommendation System  
- Mobile App Version  

---

## 🔑 Demo Credentials

> ⚠️ These are default credentials for testing/demo purposes only.

### 🛡️ Admin
- Email: `admin@gmail.com`  
- Password: `admin`

### 🎨 Artist
- Email: `artist@gmail.com`  
- Password: `artist`

### 👤 User
- Email: `user@gmail.com`  
- Password: `user123`

## 👨‍💻 Author

**Saumya Chitrakar**

---

This project is developed for academic purposes.
