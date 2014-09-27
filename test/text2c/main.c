#include <stdio.h>
int main(){
	int c;
	putc('"', stdout);
	while((c=fgetc(stdin))!=EOF){
		switch(c) {
			case '\n':
				printf("\\n\"\n\""); break;
			case '"':
			case '\\':
				putc('\\', stdout);
			default:
				putc(c, stdout);
		}
	}
	putc('"', stdout);
	return 0;
}
