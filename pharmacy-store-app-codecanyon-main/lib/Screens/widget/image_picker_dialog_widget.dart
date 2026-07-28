import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import '../../constant.dart';

class ImagePickerDialog extends StatelessWidget {
  final VoidCallback onGalleryTap;
  final VoidCallback onCameraTap;

  const ImagePickerDialog({
    super.key,
    required this.onGalleryTap,
    required this.onCameraTap,
  });

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: kWhite,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12.0),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 37),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  lang.S.of(context).choose,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                CloseButton(
                  style: ButtonStyle(
                    padding: WidgetStateProperty.all(EdgeInsets.zero),
                    backgroundColor: WidgetStateProperty.all(Color(0xffF7F7F7)),
                  ),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                GestureDetector(
                  onTap: onGalleryTap,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    spacing: 15,
                    children: [
                      SvgPicture.asset(
                        'assets/file_upload.svg',
                        width: 40,
                        height: 40,
                      ),
                      Text(
                        lang.S.of(context).gallery,
                        style: GoogleFonts.poppins(
                          fontSize: 18.0,
                          // color: kGreyTextColor,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 40.0),
                GestureDetector(
                  onTap: onCameraTap,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    spacing: 15,
                    children: [
                      SvgPicture.asset(
                        'assets/camera.svg',
                        width: 40,
                        height: 40,
                      ),
                      Text(
                        lang.S.of(context).camera,
                        style: GoogleFonts.poppins(
                          fontSize: 18.0,
                          // color: kGreyTextColor,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
