##### Library System API Documentation


### 1. **Error Handling:**

Error handling in APIs ensures that users are informed of any issues with their requests or with the backend system. Proper error handling improves the user experience and makes debugging easier.

- **Types of Errors Handled:**
  - **Token Errors:**
    - **No Token:** When the token is missing from the request body.
    - **Invalid Token:** When the provided token does not match the stored token for the given user ID.
    - **Expired Token:** When the token has expired, and the user needs to log in again.
  - **Database Errors:**
    - Errors occurring when interacting with the database (e.g., failing to execute SQL queries).
    - Custom error messages (e.g., `"Location already exists"`, `"No books found for this author"`).
  - **Request Validation Errors:** Invalid or missing parameters in the request body.
  
### 2. **Access Token:**

An **Access Token** is used to authenticate and authorize requests made to an API. In this case, JWT (JSON Web Tokens) are used.

- **JWT Structure:**
  - **Header:** Contains metadata, including the signing algorithm (HS256).
  - **Payload:** Contains claims such as the `iss` (issuer), `aud` (audience), `iat` (issued at), and `exp` (expiration).
  - **Signature:** Used to verify the token's authenticity.

- **How Access Token Works:**
  - The client sends a token in the request header (or body in some cases) to authenticate.
  - The backend validates the token, checks if it matches the expected token for the user, and confirms it is not expired.
  - If valid, the request proceeds; if invalid, an error response is returned.
  
- **Token Expiration and Renewal:**
  - If a token expires, the user is notified to log in again. 
  - After a successful operation (like updating a record), a new JWT is generated and returned in the response to keep the user session active.

### 3. **Security:**

Security in API development is crucial to protect sensitive data and prevent unauthorized access. 

- **Authentication:** 
  - Use JWT tokens for user authentication. The server validates the token on every protected route to ensure that only authorized users can access or modify data.
  
- **Authorization:** 
  - Ensure that the user has permission to perform the requested operation (e.g., a user can only update or delete data they own or are authorized to modify).
  
- **Secure Database Connections:**
  - Use parameterized queries (`$stmt->execute()`) to prevent SQL injection attacks.
  
- **Token Handling:** 
  - Tokens are sensitive and should be stored securely on both the client (e.g., in `localStorage` or `sessionStorage`) and the server (e.g., in an encrypted database field).
  
- **HTTPS:** 
  - Always use HTTPS for secure communication between the client and server to prevent eavesdropping and data tampering.

### 4. **CRUD Operation Overview:**

CRUD stands for Create, Read, Update, and Delete. These are the four basic operations that are performed on data in databases. 

#### **1. Create (`POST`):**
  - **Purpose:** Adds new data to the database.
  - **Example Endpoint:** `/add/location`
  - **Request:** Expects data (e.g., location name, user ID, and access token) to create a new entry.
  - **Response:** Returns a success message along with a new JWT if the operation is successful, or an error message if not.

#### **2. Read (`GET`):**
  - **Purpose:** Retrieves data from the database.
  - **Example Endpoint:** `/read/location` or `/read/location/{locid}`
  - **Request:** Can include query parameters or path parameters (like location ID) to retrieve specific data.
  - **Response:** Returns the requested data (e.g., a list of locations or books) or an error message if no data is found.

#### **3. Update (`PUT`):**
  - **Purpose:** Updates existing data in the database.
  - **Example Endpoint:** `/update/location/{locid}`
  - **Request:** Expects data (e.g., updated location name) and the location ID to identify which entry to modify.
  - **Response:** Returns a success message with the updated data or an error message if the update fails.

#### **4. Delete (`DELETE`):**
  - **Purpose:** Removes data from the database.
  - **Example Endpoint:** `/delete/location/{locid}`
  - **Request:** Requires the ID of the data to delete (e.g., location ID).
  - **Response:** Returns a success message if the data is deleted, or an error message if the operation fails (e.g., location not found).


### 5. **Documentation Purpose:**

API documentation helps developers and users understand how to interact with an API. It typically includes:
- **Endpoint descriptions** for all available routes.
- **Request and response formats** for each endpoint.
- **Authentication and authorization information**, including how to acquire and use the access token.
- **Error codes** and explanations for common failure cases (e.g., token expired, invalid input).
  
Good documentation ensures smooth integration with the API, improving usability and minimizing errors in API consumption.

--- 

This framework is essential to ensure your API is secure, well-documented, and provides a smooth user experience for those interacting with it.

**Endpoints for the USER**

**Endpoint**: http://localhost/library/public/user/register
**method:** POST
**payload:**
  ```json
  {
  "username":"yourname",
  "password":"secret"
}
```
**Success Response:**
  ```json
  {
  "status": "success",
  "data": null
}
```
**Error Response:**
  ```json
  {
  "status": "fail",
  "data": {
    "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
  }
}
```

**Endpoint:** `http://localhost/library/public/user/auth`  
**Method:** POST  

**Payload:**  
```json
{
  "username": "yourname",
  "password": "secret"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "token": "your_generated_jwt_token",
  "data": null
}
```

**Error Responses:**  
1. **Authentication Failure:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "authentication failed"
     }
   }
   ```
2. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
 
---

**Endpoint:** `http://localhost/library/public/user/update`  
**Method:** PUT  

**Payload:**  
```json
{
  "new_username": "newname",
  "new_password": "newsecret",
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "data": "User updated successfully",
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **No Changes Made:**  
   ```json
   {
     "status": "fail",
     "data": "No changes made"
   }
   ```
4. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
5. **Invalid Token Exception:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Invalid Token, Please Login Again"
     }
   }
   ```

---


**Endpoint:** `http://localhost/library/public/user/delete`  
**Method:** DELETE  

**Payload:**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "data": "User deleted successfully",
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **No User Found to Delete:**  
   ```json
   {
     "status": "fail",
     "data": "No user found to delete"
   }
   ```
4. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
5. **Invalid Token Exception:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Invalid Token, Please Login Again"
     }
   }
   ```

---

**Endpoint:** `http://localhost/library/public/read/user`  
**Method:** POST  

**Payload:**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "data": {
    "userid": "user_id",
    "username": "username_value"
  },
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **User Not Found:**  
   ```json
   {
     "status": "fail",
     "data": "User not found"
   }
   ```
4. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
5. **Invalid Token Exception:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Invalid Token, Please Login Again"
     }
   }
   ```

### Endpoints for BOOKS
### Here's the documentation for the /read/allbooks endpoint:

**Endpoint:** `http://localhost/library/public/add/books`  
**Method:** POST  
**Description:** Add books to the database.
**Payload:**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "loc": "location_name",
  "author": "author_name",
  "title": "book_title"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Book Already Exists:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Book with the same title and author already exists"
     }
   }
   ```
4. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
5. **Token Expired Exception:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired, Please Relogin"
     }
   }
   ```
### Here's the documentation for the `/read/allbooks` endpoint:

---

**Endpoint:** `http://localhost/library/public/read/allbooks`  
**Method:** GET  

**Description:** Retrieves a list of all books in the library database. Requires a valid JWT token for authentication.

**Payload:**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
```json
{
  "status": "success",
  "data": [
    {
      "bookid": "1",
      "title": "Sample Book Title",
      "authorid": "123",
      "locid": "456"
    },
    ...
  ],
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it"
     }
   }
   ```
   
  ### Here's the documentation for the `/read/books/{bookid}` endpoint:
---

### Endpoint: Retrieve Book by ID

**URL:** `http://localhost/library/public/read/books/{bookid}`  
**Method:** GET  

**Description:** Fetches details of a specific book identified by its `bookid`. This endpoint requires a valid JWT token for authentication.

**Path Parameter:**  
- `bookid`: The ID of the book to retrieve.

**Payload (in request body):**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
If the book is found:
```json
{
  "status": "success",
  "data": {
    "bookid": "1",
    "title": "Sample Book Title",
    "authorid": "123",
    "locid": "456"
  },
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Book Not Found:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Book not found"
     }
   }
   ```
4. **Database Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] Connection failed"
     }
   }
   ```

  ### Here's the documentation for the `/update/books/{bookid}` endpoint:

---

### Endpoint: Update Book Information

**URL:** `http://localhost/library/public/update/books/{bookid}`  
**Method:** PUT  

**Description:** Updates the details of a specific book identified by `bookid`. Requires a valid JWT token for authentication and authorization.

**Path Parameter:**  
- `bookid`: The ID of the book to update.

**Payload (in request body):**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "title": "New Book Title",
  "author": "New Author Name",
  "loc": "New Location Name"
}
```

**Success Response:**  
If the book is updated successfully:
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Token Expired:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired, Please Relogin"
     }
   }
   ```
4. **Database Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] Connection failed"
     }
   }
   ```
5. **General Database Error:**  
   For errors such as failing to insert a new author/location or update the book details:
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```

  ### Here is the documentation for the `/delete/books/{bookid}` endpoint:

---

### Endpoint: Delete Book Information

**URL:** `http://localhost/library/public/delete/books/{bookid}`  
**Method:** DELETE  

**Description:** Deletes a specific book identified by `bookid`. Requires a valid JWT token for authentication and authorization.

**Path Parameter:**  
- `bookid`: The ID of the book to delete.

**Payload (in request body):**  
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
If the book is deleted successfully:
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Token Expired:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired, Please Relogin"
     }
   }
   ```
4. **Database Connection Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "SQLSTATE[HY000] [2002] Connection failed"
     }
   }
   ```
5. **General Database Error (e.g., book not found or deletion failure):**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```

  ### Here is the documentation for the `/add/author` endpoint:

---

### Endpoint: Add Author

**URL:** `http://localhost/library/public/add/author`  
**Method:** POST  

**Description:** Adds a new author to the library system. Requires a valid JWT token for authentication and authorization.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "authorname": "author_name"
}
```

**Success Response:**  
If the author is added successfully:
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Token Expired or Invalid:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired or Invalid"
     }
   }
   ```
4. **Author Already Exists:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Author already exists"
     }
   }
   ```
5. **Database Connection or SQL Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```

  ### Here is the documentation for the `/read/allauthors` endpoint:

---

### Endpoint: Read All Authors

**URL:** `http://localhost/library/public/read/allauthors`  
**Method:** GET  

**Description:** Fetches all authors from the library system. Requires a valid JWT token for authentication and authorization.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
If the request is successful and the authors are retrieved:
```json
{
  "status": "success",
  "data": [
    {
      "authorid": 1,
      "name": "Author Name 1"
    },
    {
      "authorid": 2,
      "name": "Author Name 2"
    }
    // more authors
  ],
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Token Expired or Invalid:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired or Invalid"
     }
   }
   ```
4. **Database Connection or SQL Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```

  ### Here is the documentation for the `/read/authors/{authorid}` endpoint:

---

### Endpoint: Read Author by Author ID

**URL:** `http://localhost/library/public/read/authors/{authorid}`  
**Method:** GET  

**Description:** Fetches the list of books associated with an author using the author's ID. It also retrieves the location of each book. Requires a valid JWT token for authentication and authorization.

**URL Parameters:**
- `authorid` (required): The ID of the author whose books are being fetched.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
If the request is successful and books are found for the author:
```json
{
  "status": "success",
  "data": [
    {
      "author_name": "Author Name",
      "book_title": "Book Title 1",
      "book_location": "Location 1"
    },
    {
      "author_name": "Author Name",
      "book_title": "Book Title 2",
      "book_location": "Location 2"
    }
    // more books by the author
  ],
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **No Books Found for Author:**  
   ```json
   {
     "status": "fail",
     "data": {
       "message": "No books found for this author"
     }
   }
   ```
4. **Database Connection or SQL Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "message": "Detailed error message"
     }
   }
   ```
5. **Token Expired or Invalid:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired or Invalid"
     }
   }
   ```

  ### Here is the documentation for the `/update/authors/{authorid}` endpoint:

---

### Endpoint: Update Author by Author ID

**URL:** `http://localhost/library/public/update/authors/{authorid}`  
**Method:** PUT  

**Description:** Updates the name of an existing author identified by the given `authorid`. The request requires a valid JWT token for authentication and authorization.

**URL Parameters:**
- `authorid` (required): The ID of the author whose information is being updated.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "name": "New Author Name"
}
```

**Success Response:**  
If the request is successful:
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Database Update Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```
4. **Token Expired or Invalid:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired, Please Relogin"
     }
   }
   ```

  ### Here is the documentation for the `/delete/authors/{authorid}` endpoint:

---

### Endpoint: Delete Author by Author ID

**URL:** `http://localhost/library/public/delete/authors/{authorid}`  
**Method:** DELETE  

**Description:** Deletes the author identified by the given `authorid` from the database. This request requires a valid JWT token for authentication and authorization.

**URL Parameters:**
- `authorid` (required): The ID of the author to be deleted.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Success Response:**  
If the request is successful:
```json
{
  "status": "success",
  "data": null,
  "newToken": "your_new_jwt_token"
}
```

**Error Responses:**  
1. **No Token Found:**  
   ```json
   {
     "status": "no token",
     "data": null
   }
   ```
2. **Invalid Token:**  
   ```json
   {
     "status": "invalid token",
     "data": null
   }
   ```
3. **Database Deletion Error:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Detailed error message"
     }
   }
   ```
4. **Token Expired or Invalid:**  
   ```json
   {
     "status": "fail",
     "data": {
       "title": "Token Expired, Please Relogin"
     }
   }
   ```

Here is the documentation for the `/add/location` endpoint:

---

### Endpoint: Add Location

**URL:** `http://localhost/library/public/add/location`  
**Method:** POST  

**Description:** Adds a new location to the database. This request requires a valid JWT token for authentication and authorization. If the location already exists in the database, the request will fail.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "location": "location_name"
}
```

**Response:**

- **Success Response:**  
  If the location is successfully added:
  ```json
  {
    "status": "success",
    "data": null,
    "newToken": "your_new_jwt_token"
  }
  ```

- **Error Responses:**  
  1. **No Token Found:**  
     ```json
     {
       "status": "no token",
       "data": null
     }
     ```
  2. **Invalid Token:**  
     ```json
     {
       "status": "invalid token",
       "data": null
     }
     ```
  3. **Location Already Exists:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Location already exists"
       }
     }
     ```
  4. **Database Insertion Error:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Detailed error message"
       }
     }
     ```
  5. **Token Expired or Invalid:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Token Expired, Please Relogin"
       }
     }
     ```
### Here is the documentation for the `/read/location` endpoint:

---

### Endpoint: Read Locations

**URL:** `http://localhost/library/public/read/location`  
**Method:** GET  

**Description:** Retrieves all locations from the database. This request requires a valid JWT token for authentication and authorization. If the token is expired or invalid, an error will be returned.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Response:**

- **Success Response:**  
  If the locations are successfully retrieved:
  ```json
  {
    "status": "success",
    "data": [
      {
        "locid": "1",
        "loc": "Location Name"
      },
      {
        "locid": "2",
        "loc": "Another Location"
      }
    ],
    "newToken": "your_new_jwt_token"
  }
  ```

- **Error Responses:**  
  1. **No Token Found:**  
     ```json
     {
       "status": "no token",
       "data": null
     }
     ```
  2. **Invalid Token:**  
     ```json
     {
       "status": "invalid token",
       "data": null
     }
     ```
  3. **Database Error:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Detailed error message"
       }
     }
     ```
  4. **Token Expired or Invalid:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Token Expired, Please Relogin"
       }
     }
     ```
Here is the documentation for the `/read/location/{locid}` endpoint:

---

### Endpoint: Read Location Details by Location ID

**URL:** `http://localhost/library/public/read/location/{locid}`  
**Method:** GET  

**Description:** Retrieves details for a specific location by its `locid`, including the location name, book titles, and associated authors. This request requires a valid JWT token for authentication and authorization. If the token is expired or invalid, an error will be returned.

**Request Parameters:**
- **locid** (required): The `locid` (location ID) to fetch the details for.
  
**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Response:**

- **Success Response:**  
  If books and authors are found for the location:
  ```json
  {
    "status": "success",
    "data": [
      {
        "location_name": "Library Room 101",
        "book_title": "Introduction to Programming",
        "book_author": "John Doe"
      },
      {
        "location_name": "Library Room 101",
        "book_title": "Advanced Mathematics",
        "book_author": "Jane Smith"
      }
    ],
    "newToken": "your_new_jwt_token"
  }
  ```

- **Failure Responses:**
  1. **No Books Found for the Location:**  
     ```json
     {
       "status": "fail",
       "data": {
         "message": "No books found for this location"
       }
     }
     ```
  2. **Invalid Token:**  
     ```json
     {
       "status": "invalid token",
       "data": null
     }
     ```
  3. **No Token Found:**  
     ```json
     {
       "status": "no token",
       "data": null
     }
     ```
  4. **Database Error:**  
     ```json
     {
       "status": "fail",
       "data": {
         "message": "Detailed error message"
       }
     }
     ```
  5. **Token Expired or Invalid:**  
     ```json
     {
       "status": "fail",
       "data": {
         "message": "Token Expired, Please Relogin"
       }
     }
     ```
Here's the documentation for the `/update/location/{locid}` endpoint:

---

### Endpoint: Update Location Details by Location ID

**URL:** `http://localhost/library/public/update/location/{locid}`  
**Method:** PUT  

**Description:** Updates the name of a specific location identified by `locid`. This request requires a valid JWT token to ensure the user is authorized. If the token is expired or invalid, an error will be returned.

**Request Parameters:**
- **locid** (required): The `locid` (location ID) of the location to be updated.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id",
  "loc": "New Location Name"
}
```

**Response:**

- **Success Response:**  
  If the location is updated successfully:
  ```json
  {
    "status": "success",
    "data": null,
    "newToken": "your_new_jwt_token"
  }
  ```

- **Failure Responses:**
  1. **No Token Found:**  
     ```json
     {
       "status": "no token",
       "data": null
     }
     ```
  2. **Invalid Token:**  
     ```json
     {
       "status": "invalid token",
       "data": null
     }
     ```
  3. **Database Error (e.g., update fails):**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Error message from the database"
       }
     }
     ```
  4. **Token Expired or Invalid:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Token Expired, Please Relogin"
       }
     }
     ```

### Here's the documentation for the `/delete/location/{locid}` endpoint:

---

### Endpoint: Delete Location by Location ID

**URL:** `http://localhost/library/public/delete/location/{locid}`  
**Method:** DELETE  

**Description:** Deletes a specific location identified by `locid` from the database. The request requires a valid JWT token for authentication. If the token is expired or invalid, the request will fail.

**Request Parameters:**
- **locid** (required): The `locid` (location ID) of the location to be deleted.

**Request Body:**
```json
{
  "token": "your_jwt_token",
  "userid": "user_id"
}
```

**Response:**

- **Success Response:**  
  If the location is deleted successfully:
  ```json
  {
    "status": "success",
    "data": null,
    "newToken": "your_new_jwt_token"
  }
  ```

- **Failure Responses:**
  1. **No Token Found:**  
     ```json
     {
       "status": "no token",
       "data": null
     }
     ```
  2. **Invalid Token:**  
     ```json
     {
       "status": "invalid token",
       "data": null
     }
     ```
  3. **Database Error (e.g., delete fails):**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Error message from the database"
       }
     }
     ```
  4. **Token Expired or Invalid:**  
     ```json
     {
       "status": "fail",
       "data": {
         "title": "Token Expired, Please Relogin"
       }
     }
     ```

### Notes:
- The `jwt` token is required for authentication and authorization to add, read, update, and delete the user, books, author, and location.
- If the token is valid, a new JWT will be returned in the response.
- duplicated data will return an error response
  

