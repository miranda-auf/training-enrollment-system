# Training Enrollment System

## Project Description

The Training Enrollment System is a web application developed using PHP and MySQL. It manages training courses, class schedules, student registration, and enrollment records.

## Features

- **Dashboard:** Navigate through the system.
- **Course Management:** Add and edit training courses.
- **Class Management:** Add and edit classes, schedules, instructors, and available slots.
- **Student Registration:** Register students and select a class.
- **Student Enrollment:** Enroll existing students in classes.
- **Enrollment Records:** View, search, and cancel enrollments.
- **Slot Management:** Automatically update remaining slots when students enroll or cancel.

## Technologies Used

- PHP
- MySQL
- PDO
- HTML
- CSS
- XAMPP
- Notepad

## System Requirements

- XAMPP with Apache and MySQL
- Web browser
- Text editor such as Notepad

## Installation and Setup

1. Install XAMPP.
2. Open the XAMPP Control Panel.
3. Start Apache and MySQL.
4. Place the project folder in:

   `C:\xampp\htdocs\training_enrollment`

5. Open phpMyAdmin:

   `http://localhost/phpmyadmin`

6. Create a database named `training_db`.
7. Create the required database tables or import your project's SQL file, if available.
8. Check `config/db.php` and verify the database connection settings.

Current database settings:

- Host: `localhost`
- Database: `training_db`
- Username: `root`
- Password: empty

9. Open the system in your browser:

   `http://localhost/training_enrollment/index.php`

## Database Structure

The system uses four main database tables.

### 1. Students

Stores student information.

- `student_id`
- `full_name`
- `email`
- `phone`
- `created_at`

### 2. Courses

Stores training course information.

- `course_id`
- `course_code`
- `course_name`
- `description`

### 3. Classes

Stores class offerings for each course.

- `class_id`
- `course_id`
- `class_code`
- `schedule`
- `instructor`
- `slots`

Each class belongs to a course. The schedule, instructor, and remaining slots are stored in the Classes table.

### 4. Enrollments

Stores student enrollment information.

- `enrollment_id`
- `student_id`
- `class_id`
- `enrollment_date`
- `status`

The enrollment table connects students with their selected classes.

## Class Capacity

Each new class should initially have five available slots if the maximum capacity is five students.

The `slots` field represents the remaining available slots. When a student enrolls, the number of available slots decreases. When an enrollment is cancelled, a slot is restored.

Avoid manually resetting the slots of classes that already have enrollments.

## Project Folder Structure

```text
training_enrollment/
├── admin/
│   ├── classes.php
│   ├── courses.php
│   ├── enroll.php
│   ├── records.php
│   └── students.php
├── classes/
│   ├── ClassSection.php
│   ├── Database.php
│   ├── EnrollmentRepository.php
│   └── Student.php
├── config/
│   └── db.php
├── index.php
└── README.md
