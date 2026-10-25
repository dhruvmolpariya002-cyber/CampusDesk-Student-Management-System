import JSZip from 'jszip';
import { projectFiles } from '../data/projectFiles';

/**
 * Generate a complete, ready-to-run ZIP archive containing all project files
 * formatted directly for unpacking into XAMPP's htdocs/campusdesk directory.
 */
export async function downloadCampusDeskZip(onProgress?: (progress: number) => void): Promise<void> {
  const zip = new JSZip();

  // Populate files directly in the archive root
  // This ensures extracting to C:\xampp\htdocs\campusdesk creates C:\xampp\htdocs\campusdesk\index.php
  // and completely eliminates the Windows double-folder (campusdesk/campusdesk/) 404 trap!
  projectFiles.forEach(file => {
    zip.file(file.path, file.content);
  });

  // Generate the zip blob
  const content = await zip.generateAsync(
    { type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 9 } },
    metadata => {
      if (onProgress) {
        onProgress(Math.round(metadata.percent));
      }
    }
  );

  // Trigger client-side download
  const downloadUrl = URL.createObjectURL(content);
  const anchor = document.createElement('a');
  anchor.href = downloadUrl;
  anchor.download = 'campusdesk.zip';
  document.body.appendChild(anchor);
  anchor.click();
  document.body.removeChild(anchor);
  URL.revokeObjectURL(downloadUrl);
}
