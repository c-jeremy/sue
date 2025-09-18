
        document.addEventListener('DOMContentLoaded', function() {
            const infoIcons = document.querySelectorAll('.info-icon');
            let activePopup = null;

            function createPopup(content) {
                const popup = document.createElement('div');
                popup.className = 'fixed bg-white text-gray-800 text-sm rounded-lg shadow-lg p-4 max-w-xs z-50';
                popup.innerHTML = `
                    <button class="close-popup absolute top-1 right-1 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    ${content}
                    <div class="absolute w-3 h-3 bg-white transform rotate-45 -bottom-1.5 left-1/2 -translate-x-1/2"></div>
                `;
                document.body.appendChild(popup);
                return popup;
            }

            function showPopup(icon) {
                const content = icon.getAttribute('data-info') || "No information available.";
                const popup = createPopup(content);
                const iconRect = icon.getBoundingClientRect();
                popup.style.left = `${iconRect.left + iconRect.width / 2 - popup.offsetWidth / 2}px`;
                popup.style.top = `${iconRect.top - popup.offsetHeight - 10}px`;
                
                const closeBtn = popup.querySelector('.close-popup');
                closeBtn.addEventListener('click', () => hidePopup(popup));
                
                activePopup = popup;
            }

            function hidePopup(popup) {
                if (popup) {
                    popup.remove();
                    activePopup = null;
                }
            }

            infoIcons.forEach(icon => {
                icon.addEventListener('click', (event) => {
                    event.stopPropagation();
                    if (activePopup) {
                        hidePopup(activePopup);
                    }
                    if (!activePopup || activePopup !== event.target.nextElementSibling) {
                        showPopup(icon);
                    }
                });
            });

            document.addEventListener('click', (event) => {
                if (activePopup && !activePopup.contains(event.target)) {
                    hidePopup(activePopup);
                }
            });
        });
