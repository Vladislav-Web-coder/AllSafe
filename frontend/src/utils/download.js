/**
 * Скачивает файл по URL без блокировки pop-up.
 * Создаёт невидимую ссылку и программно кликает по ней.
 */
export function downloadFile(url, filename = 'download') {
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.target = '_blank'
  link.rel = 'noopener noreferrer'
  link.style.display = 'none'

  document.body.appendChild(link)
  link.click()

  // Удаляем после небольшого таймаута
  setTimeout(() => {
    document.body.removeChild(link)
  }, 100)
}

/**
 * Открывает URL в новой вкладке без блокировки pop-up.
 */
export function openUrl(url) {
  const link = document.createElement('a')
  link.href = url
  link.target = '_blank'
  link.rel = 'noopener noreferrer'
  link.style.display = 'none'

  document.body.appendChild(link)
  link.click()

  setTimeout(() => {
    document.body.removeChild(link)
  }, 100)
}
