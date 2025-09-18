document.addEventListener('DOMContentLoaded', () => {
    const courses = JSON.parse(document.getElementById('coursesData').textContent);
    const currentWeek = parseInt(document.getElementById('currentWeek').textContent);

    const courseContainer = document.querySelector('.course-container');
    const timeLabels = document.querySelector('.time-labels');
    const weekNumber = document.getElementById('weekNumber');
    const prevWeek = document.getElementById('prevWeek');
    const nextWeek = document.getElementById('nextWeek');

    const dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    const getTimePosition = (() => {
        const cache = new Map();
        return (time) => {
            if (!cache.has(time)) {
                const [hours, minutes] = time.split(':').map(Number);
                cache.set(time, ((hours - 7) * 60 + minutes) / (12 * 60) * 100);
            }
            return cache.get(time);
        };
    })();

    const getDayIndex = (date) => {
        const dayIndex = new Date(date).getDay() - 1;
        return dayIndex === -1 ? 6 : dayIndex;
    };

    const formatTime = (timeString) => timeString.split(' ')[1].substring(0, 5);

    function createCourseElement(course, index) {
        const startPos = getTimePosition(formatTime(course.start_time));
        const endPos = getTimePosition(formatTime(course.end_time));
        const dayIndex = getDayIndex(course.start_time.split(' ')[0]);
        const height = `${(endPos - startPos) * 0.95}%`;
        const top = `${startPos}%`;
        const left = `${(dayIndex / 7) * 100 + 0.25}%`;

        const courseElement = document.createElement('div');
        courseElement.className = 'course';
        courseElement.style.cssText = `
            top: ${top};
            left: ${left};
            height: ${height};
            width: 13.28%;
            background-color: hsl(${(index * 60) % 360}, 70%, 95%);
        `;

        courseElement.innerHTML = `
            <div class="course-title">${course.title}</div>
            <div class="course-info">${course.address}</div>
            <div class="course-info">${formatTime(course.start_time)}-${formatTime(course.end_time)}</div>
            <div class="course-info">${course.class_name}</div>
            <div class="course-info">教师: ${course.initiators.join(', ')}</div>
        `;

        courseElement.addEventListener('click', () => courseElement.classList.toggle('expanded'));

        return courseElement;
    }

    function initializeSchedule() {
        const fragment = document.createDocumentFragment();
        courses.forEach((course, index) => {
            fragment.appendChild(createCourseElement(course, index));
        });
        courseContainer.appendChild(fragment);

        const timeLabelsFragment = document.createDocumentFragment();
        for (let i = 7; i <= 19; i++) {
            const timeLabel = document.createElement('div');
            timeLabel.className = 'time-label';
            timeLabel.textContent = `${i}:00`;
            timeLabel.style.top = `${((i - 7) / 12) * 100}%`;
            if (i === 19) {
                timeLabel.style.visibility = 'hidden';
            }
            timeLabelsFragment.appendChild(timeLabel);
        }
        timeLabels.appendChild(timeLabelsFragment);
    }

    function updateWeekDisplay() {
        weekNumber.textContent = courses[0].remark || courses[courses.length - 1].remark || '';
    }

    function updateHeaderDates() {
        const startDate = new Date(courses[0].start_time);
        dayNames.forEach((day, index) => {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + index);
            const dateElement = document.getElementById(day);
            dateElement.innerHTML = `${day.charAt(0).toUpperCase() + day.slice(1)}<br><span style="font-size: 12px; color: #666;">${date.getMonth() + 1}-${date.getDate()}</span>`;
        });
    }

    function navigateWeek(direction) {
        const newWeek = currentWeek + direction;
        window.location.href = `?week=${newWeek}`;
    }

    prevWeek.addEventListener('click', () => navigateWeek(-1));
    nextWeek.addEventListener('click', () => navigateWeek(1));

    initializeSchedule();
    updateWeekDisplay();
    updateHeaderDates();
});

