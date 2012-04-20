#include <stdio.h>
#include <string.h>
#include <stdint.h>
#include <unistd.h>
#include <stdlib.h>

#define FB_WIDTH  851
#define FB_HEIGHT 315

#define LINE_SIZE	(FB_WIDTH * sizeof(uint32_t))

void usage(char *prog) {
	fprintf(stderr, "\nUsage: %s <in.jpg> <out.jpg>\n\n", prog);
}

int main(int argc, char *argv[]) {
	char cmd[255];
	FILE *f, *o;
	uint32_t *line, *counts, *averages, *colors;
	unsigned char *buf;
	int sz=0, c_idx=0, found=0, i, j, x, n, max, max_idx, counts_idx;

	if (argc != 3) {
		usage(argv[0]);
		return -1;
	}

	sprintf(cmd, "convert -depth 8 -resize %d %s tmp.rgba", FB_WIDTH, argv[1]);
	system(cmd);

	f = fopen("tmp.rgba", "rb");
	fseek(f, 0, SEEK_END);
	sz = ftell(f);
	fseek(f, 0, SEEK_SET);

	line    = malloc(LINE_SIZE);
	colors  = malloc(LINE_SIZE);
	counts  = malloc((sz / LINE_SIZE) * sizeof(uint32_t));
	memset(counts, 0, (sz / LINE_SIZE) * sizeof(uint32_t));

	counts_idx = 0;

	while(!feof(f)) {
		fread(line, 4, FB_WIDTH, f);

		c_idx = 0;
		memset(colors, 0, LINE_SIZE);

		for(j = 0; j < FB_WIDTH; j++) {
			found = 0;

			for(x = 0; x < c_idx; x++) {
				if (line[j] == colors[x]) {
					found = 1;
					break;
				}
			}

			if (found == 0) {
				colors[c_idx] = line[j];
				c_idx++;
			}
		}

		counts[counts_idx] = c_idx;
		counts_idx++; 
	}

	averages = malloc((counts_idx-FB_HEIGHT) * sizeof(uint32_t));

	for(i = 0; i < counts_idx-FB_HEIGHT; i++) {
		n = 0;

		for(j = 0; j < FB_HEIGHT; j++)
			n += counts[i+j];

		averages[i] = n;
	}

	max = 0;
	max_idx = 0;

	for(i = 0; i < counts_idx-FB_HEIGHT; i++) {
		if (averages[i] > max) {
			max = averages[i];
			max_idx = i;
		}
	}

	o = fopen("out.rgba", "wb");
	buf = malloc(FB_WIDTH * FB_HEIGHT * 4);
	fseek(f, max_idx * LINE_SIZE, SEEK_SET);
	fread(buf, 4, FB_WIDTH * FB_HEIGHT, f);
	fwrite(buf, 4, FB_WIDTH * FB_HEIGHT, o);
		
	fclose(o);
	fclose(f);

	sprintf(cmd, "convert -depth 8 -size 851x315 out.rgba %s", argv[2]);
	system(cmd);

	unlink("tmp.rgba");
	unlink("out.rgba");

	return 0;
}
