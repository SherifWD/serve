import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

export async function confirmDelete(label = 'this item', options = {}) {
  const result = await Swal.fire({
    title: options.title || 'Delete?',
    text: options.text || `This will permanently delete ${label}.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d32f2f',
    cancelButtonColor: '#64748b',
    focusCancel: true,
    reverseButtons: true,
  })

  return result.isConfirmed
}
