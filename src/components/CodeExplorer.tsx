import React, { useState } from 'react';
import { projectFiles, ProjectFile } from '../data/projectFiles';
import { downloadCampusDeskZip } from '../utils/zipGenerator';
import { Download, Copy, Check, FileCode, Folder, Search, Loader2 } from 'lucide-react';

export const CodeExplorer: React.FC = () => {
  const [selectedFile, setSelectedFile] = useState<ProjectFile>(projectFiles[0]);
  const [copied, setCopied] = useState(false);
  const [downloading, setDownloading] = useState(false);
  const [filterText, setFilterText] = useState('');

  const filteredFiles = projectFiles.filter(f => 
    f.name.toLowerCase().includes(filterText.toLowerCase()) ||
    f.path.toLowerCase().includes(filterText.toLowerCase())
  );

  const handleCopy = () => {
    navigator.clipboard.writeText(selectedFile.content);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownloadZip = async () => {
    try {
      setDownloading(true);
      await downloadCampusDeskZip();
    } catch (err) {
      alert('Error generating ZIP file: ' + err);
    } finally {
      setDownloading(false);
    }
  };

  return (
    <div className="space-y-4">
      {/* Download Action Banner */}
      <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
          <h3 className="text-sm font-bold text-slate-900">CampusDesk Source Code Package</h3>
          <p className="text-xs text-slate-500 mt-0.5">
            Download the complete, ready-to-run package for local XAMPP installation (place under <code>htdocs/campusdesk/</code>)
          </p>
        </div>
        <button
          onClick={handleDownloadZip}
          disabled={downloading}
          className="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm disabled:opacity-50"
        >
          {downloading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              Packing ZIP Archive...
            </>
          ) : (
            <>
              <Download className="w-4 h-4" />
              Download campusdesk.zip
            </>
          )}
        </button>
      </div>

      {/* Code Browser Grid */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 h-[650px]">
        {/* Left File Tree Sidebar */}
        <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex flex-col h-full overflow-hidden">
          <div className="relative mb-3">
            <Search className="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Search files..."
              value={filterText}
              onChange={e => setFilterText(e.target.value)}
              className="w-full text-xs pl-8 pr-2.5 py-1.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div className="flex-grow overflow-y-auto space-y-1 pr-1">
            {filteredFiles.map(file => (
              <button
                key={file.path}
                onClick={() => setSelectedFile(file)}
                className={`w-full text-left px-2.5 py-1.5 rounded-md text-xs font-mono transition-colors flex items-center gap-2 ${
                  selectedFile.path === file.path 
                    ? 'bg-blue-50 text-blue-700 font-bold' 
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <FileCode className="w-3.5 h-3.5 shrink-0 text-slate-400" />
                <span className="truncate">{file.path}</span>
              </button>
            ))}
          </div>
          <div className="pt-2 border-t border-slate-100 text-[11px] text-slate-400 text-center font-mono">
            {projectFiles.length} Total Project Files
          </div>
        </div>

        {/* Right Code Viewer */}
        <div className="md:col-span-3 bg-slate-900 rounded-xl shadow-xs flex flex-col h-full overflow-hidden text-slate-100 border border-slate-800">
          {/* File Header */}
          <div className="bg-slate-950/80 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="font-mono text-xs text-blue-400 font-semibold">{selectedFile.path}</span>
              <span className="text-[11px] text-slate-500 hidden sm:inline">· {selectedFile.description}</span>
            </div>
            <button
              onClick={handleCopy}
              className="inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 transition-colors"
            >
              {copied ? (
                <>
                  <Check className="w-3.5 h-3.5 text-emerald-400" />
                  <span className="text-emerald-400">Copied</span>
                </>
              ) : (
                <>
                  <Copy className="w-3.5 h-3.5" />
                  <span>Copy File</span>
                </>
              )}
            </button>
          </div>

          {/* Code Content with Line Numbers */}
          <div className="flex-grow overflow-auto p-4 font-mono text-xs leading-relaxed selection:bg-blue-500 selection:text-white">
            <pre className="text-slate-300">
              {selectedFile.content.split('\n').map((line, idx) => (
                <div key={idx} className="flex">
                  <span className="w-10 text-slate-600 select-none text-right pr-4 shrink-0">{idx + 1}</span>
                  <span className="whitespace-pre">{line}</span>
                </div>
              ))}
            </pre>
          </div>
        </div>
      </div>
    </div>
  );
};
