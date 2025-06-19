// function initSpinner() {
//     const spinner = document.querySelector('#global-spinner');
//     if (!spinner) return;
//     const toggleSpinner = (show) => spinner.classList.toggle('show', show);
//     window.addEventListener('load', () => toggleSpinner(false));
//     document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => toggleSpinner(true)));
//     window.addEventListener('beforeunload', () => toggleSpinner(true));
// }

function initNavigation() {
    const navEl = document.querySelector('.nav');
    const hamburgerEl = document.querySelector('.hamburger');

    if (navEl && hamburgerEl) {
        hamburgerEl.addEventListener('click', () => {
            navEl.classList.toggle('nav-open');
            hamburgerEl.classList.toggle('hamburger-open');
        });

        navEl.addEventListener('click', () => {
            navEl.classList.remove('nav-open');
            hamburgerEl.classList.remove('hamburger-open');
        });
    }
}

function getActivePage() {
    const link = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.nav_link');
    const contactLink = document.querySelector('.contact-btn');
    const contactButton = contactLink?.querySelector('button');

    sidebarLinks.forEach((sidebarLink) => {
        if (sidebarLink.getAttribute('href') === link) {
            sidebarLink.classList.add('active');
        } else {
            sidebarLink.classList.remove('active');
        }
    });

    if (contactLink && contactLink.getAttribute('href') === link) {
        contactButton?.classList.add('active');
    } else {
        contactButton?.classList.remove('active');
    }
}

// const Toast = Swal.mixin({
//     toast: true,
//     position: "top-end",
//     timer: 3000,
//     width: "25rem",
//     timerProgressBar: true,
//     showConfirmButton: false,
//     didOpen: (toast) => {
//         toast.onmouseenter = Swal.stopTimer;
//         toast.onmouseleave = Swal.resumeTimer;
//     }
// });

// function sweetAlert() {
//     if (successMessage) {
//         Toast.fire({
//             icon: "success",
//             text: successMessage
//         });
//     }

//     if (errorMessage) {
//         Toast.fire({
//             icon: "error",
//             text: errorMessage
//         });
//     }

//     if (warningMessage) {
//         Toast.fire({
//             icon: "warning",
//             text: warningMessage
//         });
//     }

//     if (infoMessage) {
//         Toast.fire({
//             icon: "info",
//             text: infoMessage
//         });
//     }
// }

function statusPrompt() {
    document.addEventListener('change', function (event) {
        const selection = event.target.closest('select.status');

        if (selection && selection.value === "Resigned") {
            event.preventDefault();

            Swal.fire({
                title: "Are you sure?",
                text: "All equipment and accessories assigned to this employee will be automatically marked as returned.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, I am sure!"
            }).then((result) => {
                if (result.isConfirmed) {
                    const statusForm = selection.closest('form');
                    if (statusForm) {
                        statusForm.submit();
                    }
                } else {
                    selection.selectedIndex = 0;
                }
            });
        }
    });
}

function partStatus() {
    document.addEventListener('change', function (event) {
        const selection = event.target.closest('select.part_status');

        if (selection && selection.value === "Defective") {
            event.preventDefault();

            Swal.fire({
                title: "Mark as Defective?",
                text: "This will tag the selected part(s) as defective and may affect inventory counts. This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, mark as defective"
            }).then((result) => {
                if (result.isConfirmed) {
                    const statusForm = selection.closest('form');
                    if (statusForm) {
                        statusForm.submit();
                    }
                } else {
                    selection.selectedIndex = 0;
                }
            });
        }
    });
}

function AddBtn() {
    const navItems = [
        { id: 'backBtn', btn: document.getElementById('backBtn'), content: document.getElementById('parts_table') },
        { id: 'addDataBtn', btn: document.getElementById('addDataBtn'), content: document.getElementById('addParts_content') },
        { id: 'installBtn', btn: document.getElementById('installBtn'), content: document.getElementById('install_content') }
    ];

    if (navItems.some(item => !item.btn || !item.content)) return;

    const savedTab = localStorage.getItem('activeTabParts');
    const defaultTab = navItems[0].id;
    const activeTabId = savedTab ? savedTab : defaultTab;

    navItems.forEach(item => {
        if (item.id === activeTabId) {
            item.btn.classList.add('active');
            item.content.classList.remove('hidden');
        } else {
            item.btn.classList.remove('active');
            item.content.classList.add('hidden');
        }
    });

    navItems.forEach(item => {
        item.btn.addEventListener('click', () => {
            localStorage.setItem('activeTabParts', item.id);
            window.location.reload();
        });
    });
}

function returnPrompt() {
    document.addEventListener('change', function (event) {
        const selection = event.target.closest('select.status');

        if (selection && selection.value === "Returned") {
            event.preventDefault();

            Swal.fire({
                title: "Confirm Return",
                text: "This will mark the computer as returned. The employee will remain active. This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Confirm"
            }).then((result) => {
                if (result.isConfirmed) {
                    const returnForm = selection.closest('form');
                    if (returnForm) {
                        returnForm.submit();
                    }
                } else {
                    selection.selectedIndex = 0;
                }
            });
        }
    });
}

function computerInitMenu() {
    const computerNav = document.querySelector('.computerNav');
    const computerBtns = document.getElementById('computerBtns');

    if (!computerNav || !computerBtns) return;

    computerNav.onclick = () => {
        computerBtns.classList.toggle('open');
    };
}

function computerNav() {
    const navItems = [
        { id: 'assigned', btn: document.getElementById('assignedBtn'), content: document.getElementById('assigned_content') },
        { id: 'assign', btn: document.getElementById('assignBtn'), content: document.getElementById('assign_content') },
        { id: 'return', btn: document.getElementById('returnedBtn'), content: document.getElementById('returned_content') }
    ];

    if (navItems.some(item => !item.btn || !item.content)) return;

    console.log(navItems);


    const savedTab = localStorage.getItem('activeTabComputer');
    const defaultTab = navItems[0].id;
    const activeTabId = savedTab ? savedTab : defaultTab;

    navItems.forEach(item => {
        if (item.id === activeTabId) {
            item.btn.classList.add('active');
            item.content.classList.remove('hidden');
        } else {
            item.btn.classList.remove('active');
            item.content.classList.add('hidden');
        }
    });

    navItems.forEach(item => {
        item.btn.addEventListener('click', () => {
            localStorage.setItem('activeTabComputer', item.id);
            // Refresh the page after setting the active tab.
            window.location.reload();
        });
    });
}

function uninstallBtn() {
    const uninstallBtn = document.querySelectorAll('.uninstall-btn');

    if (!uninstallBtn) return;

    uninstallBtn.forEach((btns) => {
        btns.addEventListener("click", (event) => {
            event.preventDefault();

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, I am sure!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Find the closest form and submit it
                    const uninstallForm = event.target.closest('form');
                    if (uninstallForm) {
                        console.log("Form submitted:", uninstallForm); // Debugging
                        uninstallForm.submit();
                    }
                }
            });
        });
    });
}

function addAccessories() {
    const add = document.getElementById('add');
    const back = document.getElementById('back');
    const returnHistory = document.getElementById('return');

    const addAccessories = document.getElementById('addAccessories');
    const accessoriesHistory = document.getElementById('accessoriesHistory');
    const returnedHistory = document.getElementById('returnHistory');

    if (!add || !back || !returnHistory || !addAccessories || !accessoriesHistory || !returnedHistory) return;

    const buttons = {
        add,
        back,
        returnHistory
    };

    const sections = {
        addAccessories,
        accessoriesHistory,
        returnHistory: returnedHistory
    };

    const saveActive = (btnKey) => localStorage.setItem('activeBtn', btnKey);
    const loadActive = () => localStorage.getItem('activeBtn');

    const saveSection = (sectionKey) => localStorage.setItem('visibleSection', sectionKey);
    const loadSection = () => localStorage.getItem('visibleSection') || 'addAccessories';

    const toggleVisibility = (visibleSection) => {
        for (const key in sections) {
            sections[key].classList.add('hidden');
        }
        if (sections[visibleSection]) {
            sections[visibleSection].classList.remove('hidden');
        }
    };

    const handleClick = (sectionKey, btnKey) => {
        Object.values(buttons).forEach(btn => btn.classList.remove('active'));

        toggleVisibility(sectionKey);
        saveSection(sectionKey);
        saveActive(btnKey);

        buttons[btnKey].classList.add('active');
    };

    const activeSection = loadSection();
    toggleVisibility(activeSection);

    const activeBtnKey = loadActive();
    if (buttons[activeBtnKey]) {
        buttons[activeBtnKey].classList.add('active');
    }

    add.addEventListener('click', () => handleClick('addAccessories', 'add'));
    back.addEventListener('click', () => handleClick('accessoriesHistory', 'back'));
    returnHistory.addEventListener('click', () => handleClick('returnHistory', 'returnHistory'));
}

function uploadBtn() {
    const uploadBtn = document.getElementById('uploadBtn');

    if (!uploadBtn) return;

    uploadBtn.addEventListener("click", (event) => {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure?",
            text: "Note: If you need to re-upload your signature, kindly reach out to the IT team.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                // Find the closest form and submit it
                const upload = event.target.closest('form');
                if (upload) {
                    console.log("Form submitted:", upload); // Debugging
                    upload.submit();
                }
            }
        });
    });
}

function returnAccessories() {
    document.addEventListener('change', function (event) {
        const returnBtn = event.target.closest('select.return');

        if (returnBtn && returnBtn.value === "Returned") {
            event.preventDefault();

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, I am sure!"
            }).then((result) => {
                if (result.isConfirmed) {
                    const returnForm = returnBtn.closest('form');
                    if (returnForm) {
                        returnForm.submit();
                    }
                } else {
                    returnBtn.selectedIndex = 0;
                }
            });
        }
    });
}

function openModal() {
    const modal = document.getElementById('modal');
    const overlay = document.getElementById('overlay');
    const openBtns = document.querySelectorAll('.open-modal');
    const cancelBtn = document.querySelector('#modal button[type="button"]');

    if (!modal) return;

    modal.classList.add('hidden');
    overlay.classList.add('hidden');
    // console.log("openModal initialized ✅");
    openBtns.forEach(button => {
        button.addEventListener("click", () => {
            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            overlay.classList.remove('hidden');

            // Only run this if defective-specific inputs exist
            const accessoriesIDInput = document.getElementById('getAccessoriesID');
            const prNumberInput = document.getElementById('getPRNumber');
            const brandInput = document.getElementById('getBrand');
            const accessoriesNameInput = document.getElementById('getAccessoriesName');
            const defectiveInput = document.getElementById('Defective');
            const accessory = document.getElementById('accessory');

            if (
                accessoriesIDInput &&
                prNumberInput &&
                brandInput &&
                accessoriesNameInput
            ) {
                accessoriesIDInput.value = button.dataset.accessoriesId || '';
                prNumberInput.value = button.dataset.prNumber || '';
                brandInput.value = button.dataset.brand || '';
                accessoriesNameInput.value = button.dataset.accessoriesName || '';
                accessory.innerText = button.dataset.accessoriesName + ' ' + button.dataset.brand + ' ' + button.dataset.prNumber;
                defectiveInput.value = '';
            }
        });
    });

    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => {
            modal.classList.add('hidden');
            overlay.classList.add('hidden');
        });
    }
}


function accessoriesDefective() {
    const defectBtns = document.querySelectorAll('.accessoriesDefective');

    defectBtns.forEach((button) => {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            const defectiveInput = document.getElementById('Defective');
            if (!defectiveInput || !defectiveInput.value.trim()) {
                Swal.fire({
                    title: "Missing Input",
                    text: "Please enter the number of defective units before continuing.",
                    icon: "warning",
                    confirmButtonColor: "#d33",
                    confirmButtonText: "OK"
                });
                return;
            }

            Swal.fire({
                title: "Confirm Defective Quantity",
                text: "You are about to mark a number of units of this accessory as defective. This will reduce available stock and cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, mark as defective"
            }).then((result) => {
                if (result.isConfirmed) {
                    const submitForm = button.closest('form');
                    if (submitForm) {
                        submitForm.submit();
                    }
                } else {
                    defectiveInput.value = "";
                }
            });
        });
    });
}

function setupModalReset() {
    const cancelBtn = document.querySelector('#modal-cancel');
    const overlay = document.getElementById('overlay');
    const form = document.getElementById('defective-form');

    function resetForm() {
        const defectiveInput = document.getElementById('Defective');
        if (defectiveInput) defectiveInput.value = "";
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', resetForm);
    }

    if (overlay) {
        overlay.addEventListener('click', resetForm);
    }
}


function confirmBtn() {
    const confirmBtn = document.getElementById('confirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener("click", function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                const submitForm = confirmBtn.closest('form');
                if (submitForm) {
                    submitForm.submit();
                }
            }
        });
    });
}

function userType() {
    document.addEventListener('change', function (event) {
        const selection = event.target.closest('select.type');

        if (selection) {
            if (selection.value === "Administrator" || selection.value === "Support") {
                event.preventDefault();

                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#000",
                    confirmButtonText: "Yes, I am sure!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        const userTypeForm = selection.closest('form');
                        if (userTypeForm) {
                            userTypeForm.submit();
                        }
                    } else {
                        // Reset to original value
                        selection.selectedIndex = 0;
                    }
                });
            }
        }
    });
}

function removeInvited() {
    const removeBtn = document.querySelectorAll('.removeInvited');
    if (!removeBtn) return;

    removeBtn.forEach((remove) => {
        remove.addEventListener("click", function (event) {
            event.preventDefault();
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#000",
                confirmButtonText: "Yes, I am sure!"
            }).then((result) => {
                if (result.isConfirmed) {
                    const submitForm = remove.closest('form');
                    if (submitForm) {
                        submitForm.submit();
                    }
                }
            });
        });
    })
}


function updateEmployee() {
    const updateBtn = document.getElementById('updateBtn');
    if (!updateBtn) return;

    updateBtn.addEventListener("click", function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure the details correct?",
            text: "Please double-check the information if you are unsure.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                const submitForm = updateBtn.closest('form');
                if (submitForm) {
                    submitForm.submit();
                }
            }
        });
    });
}


function updateParts() {
    const updateParts = document.getElementById('updateParts');
    if (!updateParts) return;

    updateParts.addEventListener("click", function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure the details correct?",
            text: "Please double-check the information if you are unsure.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                const submitForm = updateParts.closest('form');
                if (submitForm) {
                    submitForm.submit();
                }
            }
        });
    });
}


function addToParts() {
    const addToParts = document.getElementById('addToParts');
    if (!addToParts) return;

    addToParts.addEventListener("click", function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure the details correct?",
            text: "Please double-check the information if you are unsure.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                const submitForm = addToParts.closest('form');
                if (submitForm) {
                    submitForm.submit();
                }
            }
        });
    });
}


function updateComputer() {
    const updateComputer = document.getElementById('updateComputer');
    if (!updateComputer) return;

    updateComputer.addEventListener("click", function (event) {
        event.preventDefault();
        Swal.fire({
            title: "Are you sure the details correct?",
            text: "Please double-check the information if you are unsure.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#000",
            confirmButtonText: "Yes, I am sure!"
        }).then((result) => {
            if (result.isConfirmed) {
                const submitForm = updateComputer.closest('form');
                if (submitForm) {
                    submitForm.submit();
                }
            }
        });
    });
}

function addPRNumber() {
    const prBtn = document.getElementById('prBtn')
    const inputPR = document.getElementById('PRNumber')

    if (!prBtn || !inputPR) return;

    prBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // console.log('clicked');
        inputPR.classList.toggle('hidden')
    })
}



document.addEventListener("DOMContentLoaded", () => {
    AOS.init();
    // initSpinner();
    initNavigation();
    getActivePage();
    // sweetAlert();
    statusPrompt();
    partStatus();
    AddBtn();
    returnPrompt();
    computerInitMenu();
    computerNav();
    uninstallBtn();
    addAccessories();
    uploadBtn();
    returnAccessories();
    confirmBtn();
    accessoriesDefective();
    userType();
    removeInvited();
    openModal();
    updateComputer();
    addToParts();
    updateParts()
    updateEmployee();
    addPRNumber();
    setupModalReset();
})