#!/usr/bin/env python3
"""
Gera SHA-256 do código-fonte para cadastros (INPI, ANVISA SaMD, etc.).

Método quando o formulário não especifica o cálculo:
  1. ZIP com todos os arquivos de `git ls-files` (ordenados), conteúdo do working tree
  2. SHA-256 do arquivo ZIP (64 hex minúsculas)

Saída: storage/app/source-hash/
"""

from __future__ import annotations

import hashlib
import subprocess
import sys
import zipfile
from datetime import datetime, timezone
from pathlib import Path

REPO = Path(__file__).resolve().parents[1]
OUT_DIR = REPO / "storage/app/source-hash"
ZIP_NAME = "telemedicina-para-todos-codigo-fonte.zip"


def main() -> int:
    out_dir = OUT_DIR
    out_dir.mkdir(parents=True, exist_ok=True)
    zip_path = out_dir / ZIP_NAME

    files = subprocess.check_output(
        ["git", "ls-files"], cwd=REPO, text=True
    ).strip().split("\n")
    files = sorted(f for f in files if f)

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        for rel in files:
            path = REPO / rel
            if path.is_file():
                zf.write(path, rel)

    digest = hashlib.sha256(zip_path.read_bytes()).hexdigest()
    commit = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=REPO, text=True
    ).strip()
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S UTC")

    (out_dir / "SHA256.txt").write_text(digest + "\n")
    (out_dir / "LEIA-ME.txt").write_text(
        f"""Hash SHA-256 do código-fonte — Telemedicina Para Todos
Gerado em: {now}

Método:
  - Arquivos: `git ls-files` (ordenados), working tree atual
  - Excluídos via .gitignore: vendor/, node_modules/, .env, build/, etc.
  - Untracked não entram no pacote
  - SHA-256 do ZIP abaixo

Commit: {commit}
Arquivos: {len(files)}
ZIP: {zip_path.name} ({zip_path.stat().st_size} bytes)

SHA-256:
{digest}
"""
    )

    print(digest)
    print(f"ZIP: {zip_path} ({len(files)} arquivos)", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
