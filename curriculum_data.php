<?php
/**
 * DASES - SBTET AP C-23 Diploma Curriculum Data & Grading Rules
 * Covers 5 Core Branches:
 * 1. CME - Computer Engineering
 * 2. ECE - Electronics & Communication Engineering
 * 3. ME  - Mechanical Engineering
 * 4. EEE - Electrical & Electronics Engineering
 * 5. CE  - Civil Engineering
 */

function getC23Curriculum() {
    return [
        'CME' => [
            'name' => 'Computer Engineering',
            'code' => 'CM',
            'semesters' => [
                'Sem-1' => [ // 1st Year (Semester 1 & 2 Combined)
                    ['code' => 'CM-101', 'name' => 'English', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-102', 'name' => 'Engineering Mathematics-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-103', 'name' => 'Engineering Physics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-104', 'name' => 'Engineering Chemistry and Environmental Studies', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-105', 'name' => 'Basics of Computer Engineering', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-106', 'name' => 'Programming in C', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-107', 'name' => 'Engineering Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'CM-108', 'name' => 'Programming in C Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-109', 'name' => 'Physics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-110', 'name' => 'Chemistry Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-111', 'name' => 'Computer Fundamentals Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-3' => [
                    ['code' => 'CM-301', 'name' => 'Engineering Mathematics-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-302', 'name' => 'Digital Electronics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-303', 'name' => 'Operating Systems', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-304', 'name' => 'Data Structures Through C', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-305', 'name' => 'DBMS (Database Management Systems)', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-306', 'name' => 'Data Structures Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-307', 'name' => 'DBMS Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-308', 'name' => 'Digital Electronics & OS Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-4' => [
                    ['code' => 'CM-401', 'name' => 'Software Engineering', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-402', 'name' => 'Web Technologies', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-403', 'name' => 'Computer Organization and Microprocessors', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-404', 'name' => 'OOP through Java', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-405', 'name' => 'Computer Networks & Cyber Security', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-406', 'name' => 'Web Technologies Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-407', 'name' => 'Java Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-408', 'name' => 'Networks & Cyber Security Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-409', 'name' => 'Communication Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-5' => [
                    ['code' => 'CM-501', 'name' => 'Industrial Management & Entrepreneurship', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-502', 'name' => 'Big Data & Cloud Computing', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-503', 'name' => 'Android Programming', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-504', 'name' => 'IoT (Internet of Things)', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'CM-505', 'name' => 'Python Programming', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'CM-506', 'name' => 'Android Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-507', 'name' => 'Python Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'CM-508', 'name' => 'Life Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
            ],
        ],
        'ECE' => [
            'name' => 'Electronics and Communication Engineering',
            'code' => 'EC',
            'semesters' => [
                'Sem-1' => [
                    ['code' => 'EC-101', 'name' => 'English', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-102', 'name' => 'Engg. Maths-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-103', 'name' => 'Engg. Physics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-104', 'name' => 'Engg. Chemistry', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-105', 'name' => 'Electronic Components & Devices', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-106', 'name' => 'Elements of Electrical Engg.', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-107', 'name' => 'Engg. Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'EC-108', 'name' => 'Component & Devices Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-109', 'name' => 'Physics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-110', 'name' => 'Chemistry Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-111', 'name' => 'Workshop', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-3' => [
                    ['code' => 'EC-301', 'name' => 'Engg. Maths-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-302', 'name' => 'Electronic Circuits-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-303', 'name' => 'Digital Electronics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-304', 'name' => 'Analog & Digital Communication Systems', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-305', 'name' => 'Network Analysis', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-306', 'name' => 'Programming in C & MATLAB', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-307', 'name' => 'Electronic Circuits Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-308', 'name' => 'Digital Electronics & Communication Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-4' => [
                    ['code' => 'EC-401', 'name' => 'Electronic Circuits-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-402', 'name' => 'Microcontrollers & Interfacing', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-403', 'name' => 'Microwave & Satellite Communication Systems', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-404', 'name' => 'IoT and Sensors', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-405', 'name' => 'Digital Logic Design through Verilog HDL', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-406', 'name' => 'Microcontrollers & Verilog Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-407', 'name' => 'Communication Systems Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-408', 'name' => 'English Communication Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-5' => [
                    ['code' => 'EC-501', 'name' => 'Industrial Management & Entrepreneurship', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-502', 'name' => 'Advanced Communication & Consumer Electronics', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-503', 'name' => 'Data Communication & Computer Networks', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EC-504', 'name' => 'Industrial Electronics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-505', 'name' => 'Microcontrollers Applications', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EC-506', 'name' => 'Advanced Electronics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-507', 'name' => 'Power & Industrial Electronics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EC-508', 'name' => 'Life Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
            ],
        ],
        'ME' => [
            'name' => 'Mechanical Engineering',
            'code' => 'M',
            'semesters' => [
                'Sem-1' => [
                    ['code' => 'M-101', 'name' => 'English', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-102', 'name' => 'Engg. Maths-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-103', 'name' => 'Engg. Physics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-104', 'name' => 'Engg. Chemistry', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-105', 'name' => 'Workshop Technology', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-106', 'name' => 'Engg. Mechanics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-107', 'name' => 'Engg. Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'M-108', 'name' => 'Basic Workshop Practice', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-109', 'name' => 'Physics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-110', 'name' => 'Chemistry Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-3' => [
                    ['code' => 'M-301', 'name' => 'Engg. Maths-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-302', 'name' => 'Strength of Materials', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-303', 'name' => 'Thermal Engineering-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-304', 'name' => 'Manufacturing Technology-I', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-305', 'name' => 'Machine Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'M-306', 'name' => 'Material Testing Practice', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-307', 'name' => 'Mechanical Engineering Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-308', 'name' => 'Manufacturing Practice-I', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-4' => [
                    ['code' => 'M-401', 'name' => 'Design of Machine Elements', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-402', 'name' => 'Hydraulics & Fluid Power Systems', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-403', 'name' => 'Thermal Engineering-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-404', 'name' => 'Engineering Materials', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-405', 'name' => 'Manufacturing Technology-II', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-406', 'name' => 'Production Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'M-407', 'name' => 'Thermal Engineering Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-408', 'name' => 'Communication Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-409', 'name' => 'Hydraulics & Fluid Power Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-410', 'name' => 'Machining & Metrology Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-5' => [
                    ['code' => 'M-501', 'name' => 'Industrial Management & Entrepreneurship', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-502', 'name' => 'Industrial Engineering & Quality Control', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-503', 'name' => 'Refrigeration & Air Conditioning', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'M-504', 'name' => 'Modern Machining Processes', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-505', 'name' => 'Computer Aided Design & Manufacturing', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'M-506', 'name' => 'CAD/CAM Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-507', 'name' => 'Thermal & RAC Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'M-508', 'name' => 'Life Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
            ],
        ],
        'EEE' => [
            'name' => 'Electrical and Electronics Engineering',
            'code' => 'EE',
            'semesters' => [
                'Sem-1' => [
                    ['code' => 'EE-101', 'name' => 'English', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-102', 'name' => 'Engg. Maths-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-103', 'name' => 'Engg. Physics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-104', 'name' => 'Engg. Chemistry', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-105', 'name' => 'Basic Electrical Engineering', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-106', 'name' => 'Engineering Mechanics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-107', 'name' => 'Engg. Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'EE-108', 'name' => 'Electrical Wiring & Technology Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-109', 'name' => 'Physics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-110', 'name' => 'Chemistry Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-3' => [
                    ['code' => 'EE-301', 'name' => 'Engg. Maths-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-302', 'name' => 'Electrical Circuit Theory', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-303', 'name' => 'DC Machines & Transformers', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-304', 'name' => 'Electronic Devices & Circuits', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-305', 'name' => 'Electrical & Electronic Measuring Instruments', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-306', 'name' => 'Electrical Machines Lab-I', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-307', 'name' => 'Electronics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-308', 'name' => 'Circuits & Instruments Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-4' => [
                    ['code' => 'EE-401', 'name' => 'AC Machines-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-402', 'name' => 'Power Systems-I (Generation & Transmission)', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-403', 'name' => 'Linear ICs and Applications', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-404', 'name' => 'Microcontrollers & Electrical Applications', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-405', 'name' => 'Programming in Python', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-406', 'name' => 'Electrical Machines Lab-II', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-407', 'name' => 'Microcontrollers & Python Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-408', 'name' => 'Communication Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-5' => [
                    ['code' => 'EE-501', 'name' => 'Industrial Management & Entrepreneurship', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-502', 'name' => 'AC Machines-II & Traction', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-503', 'name' => 'Power Electronics & PLC', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-504', 'name' => 'Power Systems-II (Protection & Utilization)', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'EE-505', 'name' => 'IoT and Electric Vehicle Technology', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'EE-506', 'name' => 'Power Electronics & PLC Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-507', 'name' => 'Electrical CAD & Project Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'EE-508', 'name' => 'Life Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
            ],
        ],
        'CE' => [
            'name' => 'Civil Engineering',
            'code' => 'C',
            'semesters' => [
                'Sem-1' => [
                    ['code' => 'C-101', 'name' => 'English', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-102', 'name' => 'Engg. Maths-I', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-103', 'name' => 'Engg. Physics', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-104', 'name' => 'Engg. Chemistry', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-105', 'name' => 'Surveying-I', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-106', 'name' => 'Construction Materials & Practice', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-107', 'name' => 'Engg. Drawing', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'C-108', 'name' => 'Surveying-I Practice', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-109', 'name' => 'Physics Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-110', 'name' => 'Chemistry Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-3' => [
                    ['code' => 'C-301', 'name' => 'Engg. Maths-II', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-302', 'name' => 'Mechanics of Solids & Theory of Structures', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-303', 'name' => 'Hydraulics', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-304', 'name' => 'Surveying-II', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-305', 'name' => 'Building Materials & Construction', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-306', 'name' => 'Civil Engineering Drawing-I', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'C-307', 'name' => 'CAD Practice-I', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-308', 'name' => 'Surveying-II Practice & Plotting', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-309', 'name' => 'Material Testing Practice', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-310', 'name' => 'Hydraulics Practice', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-4' => [
                    ['code' => 'C-401', 'name' => 'Quantity Surveying-I', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-402', 'name' => 'Design of R.C. Structures', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-403', 'name' => 'Transportation Engineering', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-404', 'name' => 'Environmental Engineering-I', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-405', 'name' => 'Advanced Construction Concepts', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-406', 'name' => 'Civil Engineering Drawing-II', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'C-407', 'name' => 'CAD Practice-II', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-408', 'name' => 'Communication Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-409', 'name' => 'Environmental Engineering Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
                'Sem-5' => [
                    ['code' => 'C-501', 'name' => 'Industrial Management & Entrepreneurship', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-502', 'name' => 'Design of Steel Structures', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-503', 'name' => 'Construction Technology & Valuation', 'type' => 'theory', 'credits' => 4],
                    ['code' => 'C-504', 'name' => 'Geo-Technical Engineering', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-505', 'name' => 'Water Resources Engineering', 'type' => 'theory', 'credits' => 3],
                    ['code' => 'C-506', 'name' => 'Structural Engineering Drafting Practice', 'type' => 'lab', 'credits' => 2.5],
                    ['code' => 'C-507', 'name' => 'Geo-Technical & Highway Engineering Lab', 'type' => 'lab', 'credits' => 1.5],
                    ['code' => 'C-508', 'name' => 'Life Skills Lab', 'type' => 'lab', 'credits' => 1.5],
                ],
            ],
        ],
    ];
}

/**
 * Official SBTET AP Grade Point Scale
 * 90% and above -> O (Outstanding, 10 GP)
 * 80% to 89%   -> A+ (Excellent, 9 GP)
 * 70% to 79%   -> A (Very Good, 8 GP)
 * 60% to 69%   -> B+ (Good, 7 GP)
 * 50% to 59%   -> B (Above Average, 6 GP)
 * 40% to 49%   -> C (Pass, 5 GP)
 * Below 40%    -> F (Fail, 0 GP)
 */
function getSBTETGradeDetails($score) {
    if ($score === null) {
        return [
            'grade'  => 'PENDING',
            'desc'   => 'Pending Evaluation',
            'points' => 0,
            'color'  => '#F59E0B',
            'passed' => false
        ];
    }
    $score = (float)$score;
    if ($score >= 90) {
        return ['grade' => 'O', 'desc' => 'Outstanding', 'points' => 10, 'color' => '#10B981', 'passed' => true];
    }
    if ($score >= 80) {
        return ['grade' => 'A+', 'desc' => 'Excellent', 'points' => 9, 'color' => '#059669', 'passed' => true];
    }
    if ($score >= 70) {
        return ['grade' => 'A', 'desc' => 'Very Good', 'points' => 8, 'color' => '#3B82F6', 'passed' => true];
    }
    if ($score >= 60) {
        return ['grade' => 'B+', 'desc' => 'Good', 'points' => 7, 'color' => '#6366F1', 'passed' => true];
    }
    if ($score >= 50) {
        return ['grade' => 'B', 'desc' => 'Above Average', 'points' => 6, 'color' => '#8B5CF6', 'passed' => true];
    }
    if ($score >= 40) {
        return ['grade' => 'C', 'desc' => 'Pass', 'points' => 5, 'color' => '#D97706', 'passed' => true];
    }
    return ['grade' => 'F', 'desc' => 'Fail (Backlog)', 'points' => 0, 'color' => '#EF4444', 'passed' => false];
}

/**
 * Official SBTET AP CGPA to Percentage Conversion
 * Formula: Percentage (%) = (CGPA - 0.5) * 10
 */
function convertCGPAToPercentage($cgpa) {
    if (!is_numeric($cgpa) || (float)$cgpa <= 0) return null;
    $pct = round(((float)$cgpa - 0.5) * 10, 2);
    return max(0, min(100, $pct));
}

/**
 * Helper to lookup default credits for a subject code
 */
function getSubjectCredits($subjectCode) {
    static $flatCredits = null;
    if ($flatCredits === null) {
        $flatCredits = [];
        $curriculum = getC23Curriculum();
        foreach ($curriculum as $branch) {
            foreach ($branch['semesters'] as $sem => $subjects) {
                foreach ($subjects as $s) {
                    $code = strtoupper(trim($s['code']));
                    $flatCredits[$code] = $s['credits'];
                }
            }
        }
    }
    $codeClean = strtoupper(trim($subjectCode));
    return $flatCredits[$codeClean] ?? 4.0; // fallback to 4.0 standard credits
}
