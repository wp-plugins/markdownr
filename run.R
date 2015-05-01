args=(commandArgs(TRUE))

if(length(args)==0){
    stop("No arguments supplied.")
}else{
    for(i in 1:length(args)){
      eval(parse(text=args[[i]]))
    }
}

library(knitr)
library(markdown)
md <- tempfile(tmpdir=tmpdir)
knit(input=tmp, output=md, encoding="UTF-8")
system(paste(rstudio_pandoc_path, md, "--to html --from markdown+autolink_bare_uris+tex_math_single_backslash-implicit_figures --output", 
		out, "--smart --email-obfuscation none --standalone --section-divs"))