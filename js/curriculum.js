/**
 * DASES - C-23 Diploma Curriculum & Smart Subject Auto-fill Helper
 * Branches:
 *   - CME: Computer Engineering
 *   - ECE: Electronics and Communication Engineering
 *   - ME : Mechanical Engineering
 *   - EEE: Electrical and Electronics Engineering
 *   - CE : Civil Engineering
 */

const C23_CURRICULUM = {
    CME: {
        name: "Computer Engineering (CME)",
        prefix: "CM",
        semesters: {
            "Sem-1": [
                { code: "CM-101", name: "English", credits: 3, type: "Theory" },
                { code: "CM-102", name: "Engineering Mathematics-I", credits: 4, type: "Theory" },
                { code: "CM-103", name: "Engineering Physics", credits: 3, type: "Theory" },
                { code: "CM-104", name: "Engineering Chemistry and Environmental Studies", credits: 3, type: "Theory" },
                { code: "CM-105", name: "Basics of Computer Engineering", credits: 3, type: "Theory" },
                { code: "CM-106", name: "Programming in C", credits: 4, type: "Theory" },
                { code: "CM-107", name: "Engineering Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "CM-108", name: "Programming in C Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-109", name: "Physics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-110", name: "Chemistry Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-111", name: "Computer Fundamentals Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-3": [
                { code: "CM-301", name: "Engineering Mathematics-II", credits: 4, type: "Theory" },
                { code: "CM-302", name: "Digital Electronics", credits: 3, type: "Theory" },
                { code: "CM-303", name: "Operating Systems", credits: 4, type: "Theory" },
                { code: "CM-304", name: "Data Structures Through C", credits: 4, type: "Theory" },
                { code: "CM-305", name: "DBMS (Database Management Systems)", credits: 4, type: "Theory" },
                { code: "CM-306", name: "Data Structures Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-307", name: "DBMS Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-308", name: "Digital Electronics & OS Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-4": [
                { code: "CM-401", name: "Software Engineering", credits: 3, type: "Theory" },
                { code: "CM-402", name: "Web Technologies", credits: 4, type: "Theory" },
                { code: "CM-403", name: "Computer Organization and Microprocessors", credits: 4, type: "Theory" },
                { code: "CM-404", name: "OOP through Java", credits: 4, type: "Theory" },
                { code: "CM-405", name: "Computer Networks & Cyber Security", credits: 3, type: "Theory" },
                { code: "CM-406", name: "Web Technologies Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-407", name: "Java Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-408", name: "Networks & Cyber Security Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-409", name: "Communication Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-5": [
                { code: "CM-501", name: "Industrial Management & Entrepreneurship", credits: 3, type: "Theory" },
                { code: "CM-502", name: "Big Data & Cloud Computing", credits: 4, type: "Theory" },
                { code: "CM-503", name: "Android Programming", credits: 4, type: "Theory" },
                { code: "CM-504", name: "IoT (Internet of Things)", credits: 3, type: "Theory" },
                { code: "CM-505", name: "Python Programming", credits: 4, type: "Theory" },
                { code: "CM-506", name: "Android Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-507", name: "Python Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "CM-508", name: "Life Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ]
        }
    },
    ECE: {
        name: "Electronics and Communication Engineering (ECE)",
        prefix: "EC",
        semesters: {
            "Sem-1": [
                { code: "EC-101", name: "English", credits: 3, type: "Theory" },
                { code: "EC-102", name: "Engg. Maths-I", credits: 4, type: "Theory" },
                { code: "EC-103", name: "Engg. Physics", credits: 3, type: "Theory" },
                { code: "EC-104", name: "Engg. Chemistry", credits: 3, type: "Theory" },
                { code: "EC-105", name: "Electronic Components & Devices", credits: 3, type: "Theory" },
                { code: "EC-106", name: "Elements of Electrical Engg.", credits: 3, type: "Theory" },
                { code: "EC-107", name: "Engg. Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "EC-108", name: "Component & Devices Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-109", name: "Physics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-110", name: "Chemistry Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-111", name: "Workshop", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-3": [
                { code: "EC-301", name: "Engg. Maths-II", credits: 4, type: "Theory" },
                { code: "EC-302", name: "Electronic Circuits-I", credits: 4, type: "Theory" },
                { code: "EC-303", name: "Digital Electronics", credits: 3, type: "Theory" },
                { code: "EC-304", name: "Analog & Digital Communication Systems", credits: 4, type: "Theory" },
                { code: "EC-305", name: "Network Analysis", credits: 3, type: "Theory" },
                { code: "EC-306", name: "Programming in C & MATLAB", credits: 3, type: "Theory" },
                { code: "EC-307", name: "Electronic Circuits Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-308", name: "Digital Electronics & Communication Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-4": [
                { code: "EC-401", name: "Electronic Circuits-II", credits: 4, type: "Theory" },
                { code: "EC-402", name: "Microcontrollers & Interfacing", credits: 4, type: "Theory" },
                { code: "EC-403", name: "Microwave & Satellite Communication Systems", credits: 3, type: "Theory" },
                { code: "EC-404", name: "IoT and Sensors", credits: 3, type: "Theory" },
                { code: "EC-405", name: "Digital Logic Design through Verilog HDL", credits: 3, type: "Theory" },
                { code: "EC-406", name: "Microcontrollers & Verilog Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-407", name: "Communication Systems Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-408", name: "English Communication Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-5": [
                { code: "EC-501", name: "Industrial Management & Entrepreneurship", credits: 3, type: "Theory" },
                { code: "EC-502", name: "Advanced Communication & Consumer Electronics", credits: 4, type: "Theory" },
                { code: "EC-503", name: "Data Communication & Computer Networks", credits: 4, type: "Theory" },
                { code: "EC-504", name: "Industrial Electronics", credits: 3, type: "Theory" },
                { code: "EC-505", name: "Microcontrollers Applications", credits: 3, type: "Theory" },
                { code: "EC-506", name: "Advanced Electronics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-507", name: "Power & Industrial Electronics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EC-508", name: "Life Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ]
        }
    },
    ME: {
        name: "Mechanical Engineering (ME)",
        prefix: "M",
        semesters: {
            "Sem-1": [
                { code: "M-101", name: "English", credits: 3, type: "Theory" },
                { code: "M-102", name: "Engg. Maths-I", credits: 4, type: "Theory" },
                { code: "M-103", name: "Engg. Physics", credits: 3, type: "Theory" },
                { code: "M-104", name: "Engg. Chemistry", credits: 3, type: "Theory" },
                { code: "M-105", name: "Workshop Technology", credits: 3, type: "Theory" },
                { code: "M-106", name: "Engg. Mechanics", credits: 3, type: "Theory" },
                { code: "M-107", name: "Engg. Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "M-108", name: "Basic Workshop Practice", credits: 1.5, type: "Practical / Lab" },
                { code: "M-109", name: "Physics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-110", name: "Chemistry Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-3": [
                { code: "M-301", name: "Engg. Maths-II", credits: 4, type: "Theory" },
                { code: "M-302", name: "Strength of Materials", credits: 4, type: "Theory" },
                { code: "M-303", name: "Thermal Engineering-I", credits: 4, type: "Theory" },
                { code: "M-304", name: "Manufacturing Technology-I", credits: 3, type: "Theory" },
                { code: "M-305", name: "Machine Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "M-306", name: "Material Testing Practice", credits: 1.5, type: "Practical / Lab" },
                { code: "M-307", name: "Mechanical Engineering Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-308", name: "Manufacturing Practice-I", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-4": [
                { code: "M-401", name: "Design of Machine Elements", credits: 4, type: "Theory" },
                { code: "M-402", name: "Hydraulics & Fluid Power Systems", credits: 4, type: "Theory" },
                { code: "M-403", name: "Thermal Engineering-II", credits: 4, type: "Theory" },
                { code: "M-404", name: "Engineering Materials", credits: 3, type: "Theory" },
                { code: "M-405", name: "Manufacturing Technology-II", credits: 3, type: "Theory" },
                { code: "M-406", name: "Production Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "M-407", name: "Thermal Engineering Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-408", name: "Communication Skills Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-409", name: "Hydraulics & Fluid Power Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-410", name: "Machining & Metrology Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-5": [
                { code: "M-501", name: "Industrial Management & Entrepreneurship", credits: 3, type: "Theory" },
                { code: "M-502", name: "Industrial Engineering & Quality Control", credits: 4, type: "Theory" },
                { code: "M-503", name: "Refrigeration & Air Conditioning", credits: 4, type: "Theory" },
                { code: "M-504", name: "Modern Machining Processes", credits: 3, type: "Theory" },
                { code: "M-505", name: "Computer Aided Design & Manufacturing", credits: 3, type: "Theory" },
                { code: "M-506", name: "CAD/CAM Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-507", name: "Thermal & RAC Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "M-508", name: "Life Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ]
        }
    },
    EEE: {
        name: "Electrical and Electronics Engineering (EEE)",
        prefix: "EE",
        semesters: {
            "Sem-1": [
                { code: "EE-101", name: "English", credits: 3, type: "Theory" },
                { code: "EE-102", name: "Engg. Maths-I", credits: 4, type: "Theory" },
                { code: "EE-103", name: "Engg. Physics", credits: 3, type: "Theory" },
                { code: "EE-104", name: "Engg. Chemistry", credits: 3, type: "Theory" },
                { code: "EE-105", name: "Basic Electrical Engineering", credits: 3, type: "Theory" },
                { code: "EE-106", name: "Engineering Mechanics", credits: 3, type: "Theory" },
                { code: "EE-107", name: "Engg. Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "EE-108", name: "Electrical Wiring & Technology Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-109", name: "Physics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-110", name: "Chemistry Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-3": [
                { code: "EE-301", name: "Engg. Maths-II", credits: 4, type: "Theory" },
                { code: "EE-302", name: "Electrical Circuit Theory", credits: 4, type: "Theory" },
                { code: "EE-303", name: "DC Machines & Transformers", credits: 4, type: "Theory" },
                { code: "EE-304", name: "Electronic Devices & Circuits", credits: 3, type: "Theory" },
                { code: "EE-305", name: "Electrical & Electronic Measuring Instruments", credits: 3, type: "Theory" },
                { code: "EE-306", name: "Electrical Machines Lab-I", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-307", name: "Electronics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-308", name: "Circuits & Instruments Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-4": [
                { code: "EE-401", name: "AC Machines-I", credits: 4, type: "Theory" },
                { code: "EE-402", name: "Power Systems-I (Generation & Transmission)", credits: 4, type: "Theory" },
                { code: "EE-403", name: "Linear ICs and Applications", credits: 3, type: "Theory" },
                { code: "EE-404", name: "Microcontrollers & Electrical Applications", credits: 4, type: "Theory" },
                { code: "EE-405", name: "Programming in Python", credits: 3, type: "Theory" },
                { code: "EE-406", name: "Electrical Machines Lab-II", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-407", name: "Microcontrollers & Python Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-408", name: "Communication Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-5": [
                { code: "EE-501", name: "Industrial Management & Entrepreneurship", credits: 3, type: "Theory" },
                { code: "EE-502", name: "AC Machines-II & Traction", credits: 4, type: "Theory" },
                { code: "EE-503", name: "Power Electronics & PLC", credits: 4, type: "Theory" },
                { code: "EE-504", name: "Power Systems-II (Protection & Utilization)", credits: 4, type: "Theory" },
                { code: "EE-505", name: "IoT and Electric Vehicle Technology", credits: 3, type: "Theory" },
                { code: "EE-506", name: "Power Electronics & PLC Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-507", name: "Electrical CAD & Project Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "EE-508", name: "Life Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ]
        }
    },
    CE: {
        name: "Civil Engineering (CE)",
        prefix: "C",
        semesters: {
            "Sem-1": [
                { code: "C-101", name: "English", credits: 3, type: "Theory" },
                { code: "C-102", name: "Engg. Maths-I", credits: 4, type: "Theory" },
                { code: "C-103", name: "Engg. Physics", credits: 3, type: "Theory" },
                { code: "C-104", name: "Engg. Chemistry", credits: 3, type: "Theory" },
                { code: "C-105", name: "Surveying-I", credits: 3, type: "Theory" },
                { code: "C-106", name: "Construction Materials & Practice", credits: 3, type: "Theory" },
                { code: "C-107", name: "Engg. Drawing", credits: 2.5, type: "Practical / Lab" },
                { code: "C-108", name: "Surveying-I Practice", credits: 1.5, type: "Practical / Lab" },
                { code: "C-109", name: "Physics Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "C-110", name: "Chemistry Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-3": [
                { code: "C-301", name: "Engg. Maths-II", credits: 4, type: "Theory" },
                { code: "C-302", name: "Mechanics of Solids & Theory of Structures", credits: 4, type: "Theory" },
                { code: "C-303", name: "Hydraulics", credits: 4, type: "Theory" },
                { code: "C-304", name: "Surveying-II", credits: 3, type: "Theory" },
                { code: "C-305", name: "Building Materials & Construction", credits: 3, type: "Theory" },
                { code: "C-306", name: "Civil Engineering Drawing-I", credits: 2.5, type: "Practical / Lab" },
                { code: "C-307", name: "CAD Practice-I", credits: 1.5, type: "Practical / Lab" },
                { code: "C-308", name: "Surveying-II Practice & Plotting", credits: 1.5, type: "Practical / Lab" },
                { code: "C-309", name: "Material Testing Practice", credits: 1.5, type: "Practical / Lab" },
                { code: "C-310", name: "Hydraulics Practice", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-4": [
                { code: "C-401", name: "Quantity Surveying-I", credits: 3, type: "Theory" },
                { code: "C-402", name: "Design of R.C. Structures", credits: 4, type: "Theory" },
                { code: "C-403", name: "Transportation Engineering", credits: 4, type: "Theory" },
                { code: "C-404", name: "Environmental Engineering-I", credits: 3, type: "Theory" },
                { code: "C-405", name: "Advanced Construction Concepts", credits: 3, type: "Theory" },
                { code: "C-406", name: "Civil Engineering Drawing-II", credits: 2.5, type: "Practical / Lab" },
                { code: "C-407", name: "CAD Practice-II", credits: 1.5, type: "Practical / Lab" },
                { code: "C-408", name: "Communication Skills Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "C-409", name: "Environmental Engineering Lab", credits: 1.5, type: "Practical / Lab" }
            ],
            "Sem-5": [
                { code: "C-501", name: "Industrial Management & Entrepreneurship", credits: 3, type: "Theory" },
                { code: "C-502", name: "Design of Steel Structures", credits: 4, type: "Theory" },
                { code: "C-503", name: "Construction Technology & Valuation", credits: 4, type: "Theory" },
                { code: "C-504", name: "Geo-Technical Engineering", credits: 3, type: "Theory" },
                { code: "C-505", name: "Water Resources Engineering", credits: 3, type: "Theory" },
                { code: "C-506", name: "Structural Engineering Drafting Practice", credits: 2.5, type: "Practical / Lab" },
                { code: "C-507", name: "Geo-Technical & Highway Engineering Lab", credits: 1.5, type: "Practical / Lab" },
                { code: "C-508", name: "Life Skills Lab", credits: 1.5, type: "Practical / Lab" }
            ]
        }
    }
};

/**
 * Populate Subject Dropdown based on chosen Branch & Semester
 * @param {string} branchId - e.g. 'CME', 'ECE', 'ME', 'EEE', 'CE'
 * @param {string} semId - e.g. 'Sem-1', 'Sem-3', 'Sem-4', 'Sem-5'
 * @param {HTMLSelectElement} subjectSelectElem
 */
function updateSubjectOptions(branchId, semId, subjectSelectElem) {
    if (!subjectSelectElem) return;
    subjectSelectElem.innerHTML = '<option value="">-- Choose Subject from C-23 Curriculum --</option>';

    if (!branchId || branchId === 'custom' || !C23_CURRICULUM[branchId]) {
        const opt = document.createElement('option');
        opt.value = 'custom';
        opt.textContent = '(Manual / Custom Entry)';
        subjectSelectElem.appendChild(opt);
        return;
    }

    const branchData = C23_CURRICULUM[branchId];
    const subjects = branchData.semesters[semId] || [];

    subjects.forEach(sub => {
        const opt = document.createElement('option');
        opt.value = JSON.stringify({ code: sub.code, name: sub.name, credits: sub.credits });
        opt.textContent = `${sub.code}: ${sub.name} (${sub.type})`;
        subjectSelectElem.appendChild(opt);
    });

    const customOpt = document.createElement('option');
    customOpt.value = 'custom';
    customOpt.textContent = '-- Other / Custom Subject --';
    subjectSelectElem.appendChild(customOpt);
}

/**
 * Handle auto-fill when user selects a subject
 */
function onSubjectSelected(subjectSelectElem, nameInputElem, codeInputElem) {
    if (!subjectSelectElem || !nameInputElem || !codeInputElem) return;
    const val = subjectSelectElem.value;
    if (!val || val === 'custom') {
        return;
    }
    try {
        const item = JSON.parse(val);
        nameInputElem.value = item.name;
        codeInputElem.value = item.code;
        // Trigger subtle green flash highlight
        nameInputElem.style.transition = 'background 0.3s';
        codeInputElem.style.transition = 'background 0.3s';
        nameInputElem.style.background = '#ECFDF5';
        codeInputElem.style.background = '#ECFDF5';
        setTimeout(() => {
            nameInputElem.style.background = '';
            codeInputElem.style.background = '';
        }, 800);
    } catch (e) {
        console.error("Failed to parse subject JSON:", e);
    }
}
