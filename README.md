
# Stock Image Website 📸

A simple web-based stock image platform where users can browse and download categorized images, while admins can upload and manage them using an Oracle SQL backend.

## 🌐 Features

- User-friendly interface to **browse, filter, and download images**
- Image categorization based on **type, date, size**, and more
- **Admin dashboard** to upload images and manage content
- Backed by an **Oracle SQL Database** to store and query image metadata

## 🏗️ Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: Oracle SQL Database (for image metadata and categorization)
- **Server**: Could be extended using PHP, Node.js, or Java (depending on integration)

## 📁 Project Structure

```
stock_website/
├── index.html              # Main page for browsing images
├── admin.html              # Admin dashboard for uploads
├── css/
│   └── styles.css          # Styling files
├── js/
│   └── script.js           # Scripts for filtering and interactivity
└── sql/
    └── image_schema.sql    # Oracle SQL schema and queries
```

## 📦 Features in Progress / To-Do

- [ ] Oracle DB connection for live upload and query
- [ ] Secure admin login
- [ ] Search bar for faster filtering
- [ ] Pagination and image preview

## 🧾 Usage

### For Users

- Open `index.html` in a browser to view and download images.
- Use filters to search by category, size, or upload date.

### For Admins

- Open `admin.html` to upload images (functionality depends on backend integration).
- Backend connection must be established for live upload.

## 🗃️ Sample SQL Schema

```sql
CREATE TABLE images (
    id NUMBER PRIMARY KEY,
    filename VARCHAR2(255),
    category VARCHAR2(100),
    upload_date DATE,
    size_kb NUMBER,
    file_path VARCHAR2(255)
);
```

## 👨‍💻 Author

**Hemanth Kumar**  
GitHub: [@BL-EN-U4AIE22138-HemanthKumar](https://github.com/BL-EN-U4AIE22138-HemanthKumar)

## 📄 License

This project is licensed under the MIT License.
