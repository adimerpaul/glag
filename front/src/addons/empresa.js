export function companyData () {
  try {
    return JSON.parse(localStorage.getItem('empresaGlag') || '{}')
  } catch {
    return {}
  }
}
