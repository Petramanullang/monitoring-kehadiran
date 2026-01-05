CREATE DATABASE IF NOT EXISTS monitoring_kehadiran;
USE monitoring_kehadiran;

-- MASTER
CREATE TABLE departments (
  id_department INT AUTO_INCREMENT PRIMARY KEY,
  nama_department VARCHAR(80) NOT NULL
);

CREATE TABLE position (
  id_position INT AUTO_INCREMENT PRIMARY KEY,
  nama_position VARCHAR(80) NOT NULL
);

CREATE TABLE role (
  id_role INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(30) NOT NULL
);

CREATE TABLE status (
  id_status INT AUTO_INCREMENT PRIMARY KEY,
  status_name VARCHAR(30) NOT NULL
);

CREATE TABLE shift (
  id_shift INT AUTO_INCREMENT PRIMARY KEY,
  nama_shift VARCHAR(60) NOT NULL,
  jam_masuk TIME NOT NULL,
  jam_keluar TIME NOT NULL
);

CREATE TABLE jenis (
  id_jenis INT AUTO_INCREMENT PRIMARY KEY,
  jenis VARCHAR(30) NOT NULL
);

-- AUTH (tabel sesuai access: user)
CREATE TABLE user (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,     -- simpan hash
  id_role INT NOT NULL,
  id_status INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_role) REFERENCES role(id_role),
  FOREIGN KEY (id_status) REFERENCES status(id_status)
);

-- PEGAWAI
CREATE TABLE employee (
  id_employee INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  nip VARCHAR(30) NULL UNIQUE,
  id_position INT NOT NULL,
  id_departments INT NOT NULL,
  atasan_id INT NULL,
  id_status INT NOT NULL,

  CONSTRAINT fk_emp_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user),

  CONSTRAINT fk_emp_position
    FOREIGN KEY (id_position) REFERENCES `position`(id_position),

  CONSTRAINT fk_emp_departments
    FOREIGN KEY (id_departments) REFERENCES departments(id_department),

  CONSTRAINT fk_emp_atasan
    FOREIGN KEY (atasan_id) REFERENCES employee(id_employee),

  CONSTRAINT fk_emp_status
    FOREIGN KEY (id_status) REFERENCES status(id_status)
);


-- JADWAL
CREATE TABLE schedule (
  id_schedule INT AUTO_INCREMENT PRIMARY KEY,
  id_employee INT NOT NULL,
  tanggal DATE NOT NULL,
  id_shift INT NOT NULL,
  UNIQUE KEY uq_schedule (id_employee, tanggal),
  FOREIGN KEY (id_employee) REFERENCES employee(id_employee),
  FOREIGN KEY (id_shift) REFERENCES shift(id_shift)
);

-- ABSENSI (nama tabel di diagram kamu "attedance", tapi aku rapihin jadi attendance)
CREATE TABLE attendance (
  id_attendance INT AUTO_INCREMENT PRIMARY KEY,
  id_employee INT NOT NULL,
  tanggal DATE NOT NULL,
  check_in DATETIME NULL,
  check_out DATETIME NULL,
  UNIQUE KEY uq_att (id_employee, tanggal),
  FOREIGN KEY (id_employee) REFERENCES employee(id_employee)
);

-- IZIN
CREATE TABLE leave_request (
  id_leave INT AUTO_INCREMENT PRIMARY KEY,
  id_employee INT NOT NULL,
  id_jenis INT NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_berakhir DATE NOT NULL,
  alasan VARCHAR(255) NOT NULL,
  FOREIGN KEY (id_employee) REFERENCES employee(id_employee),
  FOREIGN KEY (id_jenis) REFERENCES jenis(id_jenis)
);

-- APPROVAL (1 request = 0/1 approval)
CREATE TABLE leave_aproval (
  id_leave INT PRIMARY KEY,
  approved_by INT NOT NULL,
  approved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  keputusan ENUM('APPROVED','REJECTED') NOT NULL,
  FOREIGN KEY (id_leave) REFERENCES leave_request(id_leave),
  FOREIGN KEY (approved_by) REFERENCES employee(id_employee)
);

-- SEED DATA
INSERT INTO role (role_name) VALUES
('admin_hr'),('pegawai'),('atasan');

INSERT INTO status (status_name) VALUES
('aktif'),('nonaktif');

INSERT INTO jenis (jenis) VALUES
('IZIN'),('SAKIT'),('CUTI');

INSERT INTO departments (nama_department) VALUES
('Umum'),('IT'),('HR');

INSERT INTO position (nama_position) VALUES
('Staff'),('Supervisor'),('Manager');

INSERT INTO shift (nama_shift, jam_masuk, jam_keluar) VALUES
('Pagi','08:00:00','16:00:00'),
('Siang','13:00:00','21:00:00');

-- Admin default:
-- username: admin
-- password: admin123
-- password hash (bcrypt):
INSERT INTO user (username, password, id_role, id_status)
VALUES ('admin', '$2b$10$hz8QHZXJidfQ0Ovwc5FaiOjHDSUUSceNfrbLdXvhW2pIGtLDH.bme', 1, 1);
