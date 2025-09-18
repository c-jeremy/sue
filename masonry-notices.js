document.addEventListener("DOMContentLoaded", () => {
  // Convert existing grid to masonry layout
  const messageContainer = document.getElementById("message-container")
  messageContainer.classList.remove("grid", "grid-cols-1", "md:grid-cols-2", "lg:grid-cols-3", "gap-4")
  messageContainer.classList.add("masonry-container")

  // Wrap existing cards in masonry items
  const cards = document.querySelectorAll(".notice-card")
  cards.forEach((card) => {
    const wrapper = document.createElement("div")
    wrapper.className = "masonry-item"
    card.parentNode.insertBefore(wrapper, card)
    wrapper.appendChild(card)
  })

  // Variables for infinite scroll
  let page = 1
  let loading = false
  let allMessagesLoaded = false

  // Improved loadMoreMessages function for masonry layout
  function loadMoreMessages() {
    if (loading || allMessagesLoaded) return
    loading = true
    page++

    const loadingElement = document.getElementById("loading")
    const noMoreElement = document.getElementById("no-more-notices")

    loadingElement.classList.remove("hidden")
    noMoreElement.classList.add("hidden")

    fetch(`/notices.php?page=${page}`, {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        return response.json()
      })
      .then((data) => {
        const container = document.getElementById("message-container")
        if (data.length === 0) {
          allMessagesLoaded = true
          loadingElement.classList.add("hidden")
          noMoreElement.classList.remove("hidden")
        } else {
          data.forEach((item, index) => {
            // Create masonry item wrapper
            const masonryItem = document.createElement("div")
            masonryItem.className = "masonry-item"

            // Create message element
            const messageElement = document.createElement("div")
            messageElement.className = "notice-card p-5 relative fade-in"
            messageElement.style.animationDelay = `${(index % 6) * 0.05}s`

            let unreadIndicator = ""
            if (!item.isread) {
              unreadIndicator = `<div class="unread-indicator"></div>`
            }

            let attachmentHTML = ""
            if (item.attachment && userId <= 2) {
              attachmentHTML = `
              <a class="tag mb-3 inline-flex" href="${escapeHTML(item.attachment)}" rel="noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                通知变量模板.xlsx
              </a>`
            }

            let signHTML = ""
            if (item.sign) {
              signHTML = `
              <div class="tag tag-secondary inline-flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                ${escapeHTML(item.sign)}
              </div>`
            }

            const previewText = item.fullContent ? strip_tags(item.fullContent.substring(0, 120)) + "..." : ""

            messageElement.innerHTML = `
            ${unreadIndicator}
            <h2 class="text-lg font-medium mb-2 text-slate-800 pr-4">${escapeHTML(item.title)}</h2>
            
            <div class="mb-3 text-xs text-slate-500 flex items-center gap-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span>${escapeHTML(item.sender)}</span>
              ${item.sign ? `<span class="mx-1">•</span><span>${escapeHTML(item.sign)}</span>` : ""}
            </div>
            
            ${attachmentHTML}
            
            <div class="content-preview text-sm text-slate-600 mb-3">
              ${previewText}
            </div>
            
            <details class="mt-2">
              <summary class="cursor-pointer flex items-center justify-between text-teal-500 hover:text-teal-600 text-sm font-medium transition-colors duration-200 rounded-md hover:bg-teal-50 p-1.5" onclick="isread('${item.id}')">
                <span>View Details</span>
                <svg class="w-4 h-4 transform transition-transform duration-300 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </summary>
              <div class="mt-3 pl-3 border-l border-teal-200 custom-scrollbar">
                <div class="mb-3 overflow-x-auto prose prose-sm max-w-none prose-headings:text-teal-700 prose-a:text-teal-600 prose-a:no-underline hover:prose-a:text-teal-500">
                  ${item.fullContent}
                </div>
                ${signHTML}
              </div>
            </details>
          `

            // Add to DOM
            masonryItem.appendChild(messageElement)
            container.appendChild(masonryItem)

            // Trigger animation
            setTimeout(() => {
              messageElement.style.opacity = "1"
            }, 50)
          })
        }
        loading = false
        loadingElement.classList.add("hidden")
      })
      .catch((error) => {
        console.error("Error loading more messages:", error)
        const loadingElement = document.getElementById("loading")
        loadingElement.innerHTML = `
        <div class="text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <p class="text-red-500 text-sm font-medium">Error loading notices</p>
          <button class="mt-3 px-3 py-1.5 bg-teal-100 text-teal-700 rounded-md hover:bg-teal-200 transition-colors text-sm" onclick="loadMoreMessages()">Try Again</button>
        </div>
      `
        loading = false
      })
  }

  // Improved scroll detection for masonry layout
  function checkForNewContent() {
    // Get all columns in the masonry layout
    const columns = Array.from(document.querySelectorAll(".masonry-container > .masonry-item"))

    if (columns.length === 0) return

    // Find the shortest column
    const columnHeights = columns.map((col) => col.getBoundingClientRect().bottom)
    const shortestColumnHeight = Math.min(...columnHeights)

    // Calculate distance from bottom of viewport to bottom of page
    const scrollPosition = window.scrollY + window.innerHeight
    const triggerPosition = shortestColumnHeight - 500

    // Load more content if we're close to the bottom of the shortest column
    if (scrollPosition >= triggerPosition && !loading && !allMessagesLoaded) {
      loadMoreMessages()
    }
  }

  // Debounce function (keep your existing one)
  function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  }

  // Use the improved scroll detection with debounce
  const debouncedCheckForNewContent = debounce(checkForNewContent, 200)
  window.addEventListener("scroll", debouncedCheckForNewContent)
  window.addEventListener("resize", debouncedCheckForNewContent)

  // Initial check in case the page doesn't have a scrollbar
  if (document.body.offsetHeight <= window.innerHeight) {
    checkForNewContent()
  }

  // Mock functions for demonstration purposes.  In a real application,
  // these would be defined elsewhere, likely server-side.
  function escapeHTML(str) {
    var p = document.createElement("p")
    p.appendChild(document.createTextNode(str))
    return p.innerHTML
  }

  function strip_tags(input, allowed) {
    allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join("") // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
      commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi
    return input
      .replace(commentsAndPhpTags, "")
      .replace(tags, ($0, $1) => (allowed.indexOf("<" + $1.toLowerCase() + ">") > -1 ? $0 : ""))
  }

  // Mock $_SESSION['user_id'] for demonstration.  In a real application,
  // this would be set server-side.
  const userId = 1
})

