import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.PrintWriter;
import java.io.BufferedWriter; 
import java.io.FileWriter;

import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;



public class Parser_Big {

	public static void main(String[] args) throws FileNotFoundException {
		// TODO Auto-generated method stub
		String dirpath= "HTML_files";
		
		PrintWriter bw = new PrintWriter("big.txt");
		
		File dir= new File(dirpath);
		
		int numFiles = dir.listFiles().length;
		System.out.println(numFiles);
		File listOfFiles[] = dir.listFiles();

		for(int i=0;i<numFiles;i++) {
			File file = listOfFiles[i];
			
			BodyContentHandler handler = new BodyContentHandler(-1);
		    Metadata metadata = new Metadata();
		    
		    FileInputStream inputstream = null;
			try {
				inputstream = new FileInputStream(file);
			} catch (FileNotFoundException e2) {
				// TODO Auto-generated catch block
				e2.printStackTrace();
			}
			
		    ParseContext pcontext = new ParseContext();
		    
		    HtmlParser htmlparser = new HtmlParser();
		    try {
				htmlparser.parse(inputstream, handler, metadata, pcontext);
			} catch (IOException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			} catch (SAXException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			} catch (TikaException e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		    
		    String text = handler.toString();
		    text = text.trim().replaceAll("\\s+", " ");
		    String words[]= text.split(" ");
		    
		    for(String word: words) {
		    	//if(word.matches("[a-zA-Z0-9]+\\.?")) {
		    		bw.write(word + " ");
		    	//}
		    }
		}
		

		bw.flush();
		bw.close();
	
	}

}